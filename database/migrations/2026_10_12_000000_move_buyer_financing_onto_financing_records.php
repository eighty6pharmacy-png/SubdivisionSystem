<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Money columns that used to be entered on the buyer and again on the financing record.
     *
     * @var list<string>
     */
    private array $buyerMoneyColumns = [
        'financing_method',
        'contract_amount',
        'improvement_amount',
        'equity',
        'loan_base',
        'loan_term',
        'monthly_amortization',
        'mri_sri',
        'insurance',
        'mri_ds_1time',
        'nonlife_1time',
        'retention',
        'total_deductions',
    ];

    public function up(): void
    {
        Schema::table('financing_records', function (Blueprint $table) {
            $table->string('financing_method')->nullable();
            $table->decimal('loan_base', 15, 2)->default(0);
            $table->integer('loan_term')->nullable();
            $table->decimal('monthly_amortization', 15, 2)->default(0);
        });

        $buyers = DB::table('buyers')->get();
        foreach ($buyers as $buyer) {
            $this->moveBuyerFinancing($buyer);
        }

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropColumn($this->buyerMoneyColumns);
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table) {
            $table->string('financing_method')->nullable();
            $table->decimal('contract_amount', 15, 2)->nullable();
            $table->decimal('improvement_amount', 15, 2)->nullable();
            $table->decimal('equity', 15, 2)->nullable();
            $table->decimal('loan_base', 15, 2)->nullable();
            $table->integer('loan_term')->nullable();
            $table->decimal('monthly_amortization', 15, 2)->nullable();
            $table->decimal('mri_sri', 15, 2)->nullable();
            $table->decimal('insurance', 15, 2)->nullable();
            $table->decimal('mri_ds_1time', 15, 2)->nullable();
            $table->decimal('nonlife_1time', 15, 2)->nullable();
            $table->decimal('retention', 15, 2)->nullable();
            $table->decimal('total_deductions', 15, 2)->nullable();
        });

        foreach (DB::table('financing_records')->get() as $record) {
            DB::table('buyers')->where('id', $record->buyer_id)->update([
                'financing_method' => $record->financing_method,
                'contract_amount' => $record->contract_price,
                'improvement_amount' => $record->improvements_fee,
                'equity' => $record->paid_by_vendee_equity,
                'loan_base' => $record->loan_base,
                'loan_term' => $record->loan_term,
                'monthly_amortization' => $record->monthly_amortization,
                'mri_sri' => $record->sri_mri,
                'insurance' => $record->pag_ibig_non_life,
                'mri_ds_1time' => $record->interim_mri,
                'nonlife_1time' => $record->pag_ibig_non_life,
                'retention' => $record->retention_fee,
                'total_deductions' => $record->total_amt_due,
            ]);
        }

        Schema::table('financing_records', function (Blueprint $table) {
            $table->dropColumn(['financing_method', 'loan_base', 'loan_term', 'monthly_amortization']);
        });
    }

    private function moveBuyerFinancing(object $buyer): void
    {
        $record = DB::table('financing_records')->where('buyer_id', $buyer->id)->first();
        if (! $record && ! $this->buyerHasFinancing($buyer)) {
            return;
        }

        $recalculate = $record === null;
        $nonLife = (float) ($record?->pag_ibig_non_life ?? 0);
        if ($this->blank($record?->pag_ibig_non_life)) {
            $fromBuyer = (float) ($buyer->insurance ?? 0) + (float) ($buyer->nonlife_1time ?? 0);
            if ($fromBuyer > 0) {
                $nonLife = $fromBuyer;
                $recalculate = true;
            }
        }

        $values = [
            'financing_method' => $record?->financing_method ?? $buyer->financing_method,
            'contract_price' => $this->prefer($record?->contract_price, $buyer->contract_amount, $recalculate),
            'improvements_fee' => $this->prefer($record?->improvements_fee, $buyer->improvement_amount, $recalculate),
            'paid_by_vendee_equity' => $this->prefer($record?->paid_by_vendee_equity, $buyer->equity, $recalculate),
            'loan_base' => $this->prefer($record?->loan_base, $buyer->loan_base),
            'loan_term' => $record?->loan_term ?? $buyer->loan_term,
            'monthly_amortization' => $this->prefer($record?->monthly_amortization, $buyer->monthly_amortization),
            'sri_mri' => $this->prefer($record?->sri_mri, $buyer->mri_sri, $recalculate),
            'pag_ibig_non_life' => $nonLife,
            'interim_mri' => $this->prefer($record?->interim_mri, $buyer->mri_ds_1time, $recalculate),
            'retention_fee' => $this->prefer($record?->retention_fee, $buyer->retention, $recalculate),
        ];

        if ($record && ! $recalculate) {
            DB::table('financing_records')->where('id', $record->id)->update([
                'financing_method' => $values['financing_method'],
                'loan_base' => $values['loan_base'],
                'loan_term' => $values['loan_term'],
                'monthly_amortization' => $values['monthly_amortization'],
                'updated_at' => now(),
            ]);

            return;
        }

        $lotId = $record?->lot_id ?? $buyer->lot_id;
        if (! $lotId) {
            $lotId = DB::table('reservations')->where('buyer_id', $buyer->id)->whereNotNull('lot_id')->value('lot_id');
        }
        if (! $lotId) {
            throw new RuntimeException('Buyer '.$buyer->id.' has financing amounts but no lot, so they cannot be moved onto a financing record.');
        }

        $base = $record ? (array) $record : [
            'mbrdc_add_fees' => 0,
            'other_exp_annotation' => 0,
            'inspection_fee' => 0,
            'mbrdc_turn_over_fee' => 0,
            'additional_bill_of_materials' => 0,
            'loan_release' => 0,
            'amount_paid' => 0,
            'retention_on_hold' => true,
        ];

        $totalConsideration = (float) $values['contract_price']
            + (float) ($base['mbrdc_add_fees'] ?? 0)
            + (float) ($base['other_exp_annotation'] ?? 0)
            + (float) $values['improvements_fee'];
        $retentionOnHold = filter_var($base['retention_on_hold'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $totalDeductions = (float) ($base['inspection_fee'] ?? 0)
            + ($retentionOnHold ? (float) $values['retention_fee'] : 0)
            + (float) $values['sri_mri']
            + (float) $values['pag_ibig_non_life']
            + (float) $values['interim_mri'];
        $loanRelease = (float) ($base['loan_release'] ?? 0);
        $receivables = max(0, $totalConsideration - (float) $values['paid_by_vendee_equity'] - $loanRelease
            + (float) ($base['additional_bill_of_materials'] ?? 0)
            + (float) ($base['mbrdc_turn_over_fee'] ?? 0));
        $amountPaid = (float) ($base['amount_paid'] ?? 0);

        $payload = array_merge($values, [
            'total_consideration' => $totalConsideration,
            'mbrdc_amt_due_for_financing' => $totalConsideration - (float) $values['paid_by_vendee_equity'],
            'total_amt_due' => $totalDeductions,
            'net_loan_proceeds' => $loanRelease - $totalDeductions,
            'receivables' => $receivables,
            'balance' => max(0, $receivables - $amountPaid),
            'updated_at' => now(),
        ]);

        if ($record) {
            DB::table('financing_records')->where('id', $record->id)->update($payload);

            return;
        }

        DB::table('financing_records')->insert(array_merge($payload, [
            'id' => (string) Str::uuid(),
            'buyer_id' => $buyer->id,
            'lot_id' => $lotId,
            'status' => $buyer->financing_method === 'Cash' ? 'Cleared' : 'Pending',
            'created_at' => now(),
        ]));
    }

    private function buyerHasFinancing(object $buyer): bool
    {
        foreach ($this->buyerMoneyColumns as $column) {
            $value = $buyer->{$column} ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            if (is_numeric($value) && (float) $value == 0.0) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function prefer(mixed $current, mixed $fromBuyer, ?bool &$filledFromBuyer = null): float
    {
        if (! $this->blank($current)) {
            return (float) $current;
        }

        $value = (float) ($fromBuyer ?? 0);
        if ($filledFromBuyer !== null && $value > 0) {
            $filledFromBuyer = true;
        }

        return $value;
    }

    private function blank(mixed $value): bool
    {
        return $value === null || $value === '' || (is_numeric($value) && (float) $value == 0.0);
    }
};
