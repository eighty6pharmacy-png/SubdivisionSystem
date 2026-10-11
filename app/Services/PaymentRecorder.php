<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\DownpaymentStatus;
use App\Enums\FinancingStatus;
use App\Enums\PaymentHistoryStatus;
use App\Exceptions\PaymentRejectedException;
use App\Models\Downpayment;
use App\Models\DownpaymentHistory;
use App\Models\FinancingPaymentHistory;
use App\Models\FinancingRecord;
use App\Models\Payment;
use App\Models\UtilityBill;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single entry point for applying a payment to a utility bill, downpayment or financing record.
 *
 * Every method locks the parent row, is idempotent by PayMongo payment id (null for office
 * payments), always writes a history row, and clears the parent's checkout id after an online payment.
 */
class PaymentRecorder
{
    /**
     * @param  float|null  $totalDue  When given (office payments), rejects overpayment and marks the bill
     *                                paid only once fully covered. When null (online checkout of the full
     *                                amount), the bill is marked paid.
     *
     * @throws PaymentRejectedException
     */
    public function recordUtilityPayment(
        UtilityBill $bill,
        float $amount,
        string $method,
        ?string $paymongoPaymentId = null,
        ?string $trn = null,
        $paidAt = null,
        ?float $totalDue = null
    ): Payment {
        $payment = $this->idempotent(Payment::class, $paymongoPaymentId, function () use ($bill, $amount, $method, $paymongoPaymentId, $trn, $paidAt, $totalDue) {
            $locked = UtilityBill::whereKey($bill->getKey())->lockForUpdate()->firstOrFail();

            if ($existing = $this->findByPaymongoId(Payment::class, $paymongoPaymentId)) {
                return $existing;
            }

            $totalPaid = (float) Payment::where('utility_bill_id', $locked->id)->sum('amount_paid');

            if ($totalDue !== null) {
                $remaining = round($totalDue - $totalPaid, 2);
                if ($remaining <= 0) {
                    throw new PaymentRejectedException('Bill is already fully paid.');
                }
                if ($amount > $remaining) {
                    throw new PaymentRejectedException('Payment amount exceeds the remaining balance.');
                }
            }

            $payment = Payment::create([
                'utility_bill_id' => $locked->id,
                'amount_paid' => $amount,
                'method' => $method,
                'trn' => $this->uniqueTrn(Payment::class, $trn ?? ('TRN-' . strtoupper(Str::random(8)))),
                'payment_date' => $paidAt ?? now(),
                'paymongo_payment_id' => $paymongoPaymentId,
            ]);

            $updates = [];
            if ($totalDue === null || ($totalPaid + $amount) >= $totalDue - 0.01) {
                $updates['status'] = BillStatus::Paid->value;
            }
            if ($paymongoPaymentId !== null) {
                $updates['paymongo_checkout_id'] = null;
                $updates['paymongo_checkout_url'] = null;
            }
            if ($updates) {
                $locked->update($updates);
            }

            return $payment;
        });

        $bill->refresh();

        return $payment;
    }

    public function recordDownpaymentPayment(
        Downpayment $downpayment,
        float $amount,
        string $method,
        ?string $paymongoPaymentId = null,
        ?string $trn = null,
        $paidAt = null
    ): DownpaymentHistory {
        $history = $this->idempotent(DownpaymentHistory::class, $paymongoPaymentId, function () use ($downpayment, $amount, $method, $paymongoPaymentId, $trn, $paidAt) {
            $dp = Downpayment::whereKey($downpayment->getKey())->lockForUpdate()->firstOrFail();

            if ($existing = $this->findByPaymongoId(DownpaymentHistory::class, $paymongoPaymentId)) {
                return $existing;
            }

            $principalDue = round(min((float) ($dp->monthly_amortization ?? $amount), (float) $dp->balance), 2);
            $credit = ($dp->latePenaltyAmount($principalDue) > 0 && $amount > $principalDue)
                ? $principalDue
                : min($amount, (float) $dp->balance);

            $dp->amount += $credit;
            $dp->balance = round((float) $dp->balance - $credit, 2);
            if ($dp->balance <= 0) {
                $dp->status = DownpaymentStatus::FullyPaid->value;
                $dp->balance = 0;
            } else {
                $dp->status = DownpaymentStatus::GoodStanding->value;
            }
            $dp->due_date = Carbon::parse($dp->due_date)->addMonth();
            if ($paymongoPaymentId !== null) {
                $dp->paymongo_checkout_id = null;
            }
            $dp->save();

            return DownpaymentHistory::create([
                'downpayment_id' => $dp->id,
                'amount' => $amount,
                'payment_date' => $paidAt ?? now(),
                'trn' => $this->uniqueTrn(DownpaymentHistory::class, $trn ?? strtoupper(Str::random(10))),
                'payment_method' => $method,
                'status' => PaymentHistoryStatus::Paid->value,
                'paymongo_payment_id' => $paymongoPaymentId,
            ]);
        });

        $downpayment->refresh();

        return $history;
    }

    public function recordFinancingPayment(
        FinancingRecord $record,
        float $amount,
        string $method,
        ?string $paymongoPaymentId = null,
        ?string $trn = null,
        $paidAt = null
    ): FinancingPaymentHistory {
        $history = $this->idempotent(FinancingPaymentHistory::class, $paymongoPaymentId, function () use ($record, $amount, $method, $paymongoPaymentId, $trn, $paidAt) {
            $fin = FinancingRecord::whereKey($record->getKey())->lockForUpdate()->firstOrFail();

            if ($existing = $this->findByPaymongoId(FinancingPaymentHistory::class, $paymongoPaymentId)) {
                return $existing;
            }

            $fin->amount_paid += $amount;
            $fin->balance = max(0, $fin->receivables - $fin->amount_paid);
            if ($fin->balance <= 0 && $fin->status == FinancingStatus::Released->value) {
                $fin->status = FinancingStatus::Cleared->value;
            }
            if ($paymongoPaymentId !== null) {
                $fin->paymongo_checkout_id = null;
            }
            $fin->save();

            return FinancingPaymentHistory::create([
                'financing_record_id' => $fin->id,
                'amount_paid' => $amount,
                'payment_date' => $paidAt ?? now(),
                'trn' => $this->uniqueTrn(FinancingPaymentHistory::class, $trn ?? strtoupper(Str::random(10))),
                'status' => PaymentHistoryStatus::Paid->value,
                'payment_method' => $method,
                'paymongo_payment_id' => $paymongoPaymentId,
            ]);
        });

        $record->refresh();

        return $history;
    }

    /**
     * First payment with status "paid" from a checkout session's payments list.
     */
    public static function paidPayment(array $payments): ?array
    {
        return collect($payments)->first(function ($payment) {
            return ($payment['attributes']['status'] ?? '') === 'paid';
        });
    }

    /**
     * Idempotency key: the PayMongo payment id, falling back to its payment intent id.
     */
    public static function paymongoPaymentId(?array $payment, array $sessionAttributes = []): ?string
    {
        return $payment['id']
            ?? $payment['attributes']['payment_intent_id']
            ?? $sessionAttributes['payment_intent']['id']
            ?? $sessionAttributes['payment_intent_id']
            ?? null;
    }

    public static function methodFromSource(?string $sourceType, string $default): string
    {
        if ($sourceType === 'gcash') {
            return 'GCash';
        }

        return $sourceType ? ucfirst($sourceType) : $default;
    }

    /**
     * TRN for an online payment: "<payment intent or random>-<first 8 chars of the parent id>".
     */
    public static function onlineTrn(?string $paymentIntentId, string $parentId): string
    {
        return ($paymentIntentId ?: ('PM-' . strtoupper(Str::random(8)))) . '-' . substr($parentId, 0, 8);
    }

    /**
     * Runs the write in a transaction; if a concurrent request already stored the same PayMongo
     * payment (unique index violation), returns that row instead of failing.
     */
    private function idempotent(string $historyClass, ?string $paymongoPaymentId, callable $callback): Model
    {
        try {
            return DB::transaction($callback);
        } catch (UniqueConstraintViolationException $e) {
            if ($existing = $this->findByPaymongoId($historyClass, $paymongoPaymentId)) {
                return $existing;
            }
            throw $e;
        }
    }

    private function findByPaymongoId(string $historyClass, ?string $paymongoPaymentId): ?Model
    {
        if ($paymongoPaymentId === null) {
            return null;
        }

        $query = $historyClass::query();
        if (method_exists($historyClass, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->where('paymongo_payment_id', $paymongoPaymentId)->first();
    }

    private function uniqueTrn(string $historyClass, string $trn): string
    {
        $query = fn () => method_exists($historyClass, 'bootSoftDeletes')
            ? $historyClass::withTrashed()
            : $historyClass::query();

        $candidate = $trn;
        while ($query()->where('trn', $candidate)->exists()) {
            $candidate = $trn . '-' . strtoupper(Str::random(4));
        }

        return $candidate;
    }
}
