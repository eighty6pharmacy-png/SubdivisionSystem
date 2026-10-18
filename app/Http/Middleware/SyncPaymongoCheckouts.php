<?php

namespace App\Http\Middleware;

use App\Enums\BillStatus;
use App\Services\PaymentRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncPaymongoCheckouts
{
    public function __construct(private PaymentRecorder $recorder)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->hasRole('Resident')) {
                $this->syncResidentBills($user);
            } elseif ($user->hasRole('Buyer')) {
                $this->syncBuyerBills($user);
            }
        }

        return $next($request);
    }

    private function fetchCheckoutSession(string $checkoutId): ?array
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
        ])->get('https://api.paymongo.com/v1/checkout_sessions/' . $checkoutId);

        return $response->successful() ? $response->json() : null;
    }

    private function syncResidentBills($user)
    {
        $userLotIds = $user->lots()->pluck('lots.id');
        $pendingBills = \App\Models\UtilityBill::where(function ($q) use ($user, $userLotIds) {
            $q->where('resident_id', $user->id);
            if ($userLotIds->isNotEmpty()) {
                $q->orWhereIn('lot_id', $userLotIds);
            }
        })
            ->where('status', '!=', BillStatus::Paid->value)
            ->whereNotNull('paymongo_checkout_id')
            ->get();
            
        foreach ($pendingBills as $bill) {
            if (!$bill->resident_id) {
                $bill->update(['resident_id' => $user->id]);
            }
            try {
                $sessionData = $this->fetchCheckoutSession($bill->paymongo_checkout_id);
                
                if ($sessionData) {
                    $sessionAttributes = $sessionData['data']['attributes'] ?? [];
                    $paidPayment = PaymentRecorder::paidPayment($sessionAttributes['payments'] ?? []);
                    
                    if ($paidPayment) {
                        $method = PaymentRecorder::methodFromSource($paidPayment['attributes']['source']['type'] ?? '', 'GCash');
                        $trn = PaymentRecorder::onlineTrn($paidPayment['attributes']['payment_intent_id'] ?? null, (string) $bill->id);
                        $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : ($bill->amount + $bill->previous_balance);
                        $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : now();

                        $this->recorder->recordUtilityPayment(
                            $bill,
                            (float) $exactAmount,
                            $method,
                            PaymentRecorder::paymongoPaymentId($paidPayment, $sessionAttributes),
                            $trn,
                            $paidAt
                        );
                    } elseif (($sessionAttributes['status'] ?? '') === 'expired') {
                        $bill->update([
                            'paymongo_checkout_id' => null,
                            'paymongo_checkout_url' => null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Sync error: ' . $e->getMessage());
            }
        }
    }

    private function syncBuyerBills($user)
    {
        $buyer = $user->buyerMasterList;
        if (!$buyer) return;

        $this->syncDownpayments($buyer);
        $this->syncFinancing($buyer);
    }

    private function syncDownpayments($buyer)
    {
        $pendingDps = \App\Models\Downpayment::whereHas('reservation', function($q) use ($buyer) {
            $q->where('buyer_id', $buyer->id);
        })->where('balance', '>', 0)
          ->whereNotNull('paymongo_checkout_id')
          ->get();

        foreach ($pendingDps as $dp) {
            try {
                $sessionData = $this->fetchCheckoutSession($dp->paymongo_checkout_id);

                if ($sessionData) {
                    $sessionAttributes = $sessionData['data']['attributes'] ?? [];
                    $paidPayment = PaymentRecorder::paidPayment($sessionAttributes['payments'] ?? []);

                    if ($paidPayment) {
                        $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : $dp->monthly_amortization;
                        $method = PaymentRecorder::methodFromSource($paidPayment['attributes']['source']['type'] ?? '', 'Online Payment');
                        $trn = PaymentRecorder::onlineTrn($paidPayment['attributes']['payment_intent_id'] ?? null, $dp->id);

                        $this->recorder->recordDownpaymentPayment(
                            $dp,
                            (float) $exactAmount,
                            $method,
                            PaymentRecorder::paymongoPaymentId($paidPayment, $sessionAttributes),
                            $trn
                        );
                    } elseif (($sessionAttributes['status'] ?? '') === 'expired') {
                        $dp->update([
                            'paymongo_checkout_id' => null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('DP Sync error: ' . $e->getMessage());
            }
        }
    }

    private function syncFinancing($buyer)
    {
        $fin = \App\Models\FinancingRecord::where('buyer_id', $buyer->id)
            ->where('balance', '>', 0)
            ->whereNotNull('paymongo_checkout_id')
            ->first();

        if ($fin) {
            try {
                $sessionData = $this->fetchCheckoutSession($fin->paymongo_checkout_id);

                if ($sessionData) {
                    $sessionAttributes = $sessionData['data']['attributes'] ?? [];
                    $paidPayment = PaymentRecorder::paidPayment($sessionAttributes['payments'] ?? []);

                    if ($paidPayment) {
                        $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : $fin->balance;
                        $method = PaymentRecorder::methodFromSource($paidPayment['attributes']['source']['type'] ?? '', 'Online Payment');
                        $trn = PaymentRecorder::onlineTrn($paidPayment['attributes']['payment_intent_id'] ?? null, $fin->id);

                        $this->recorder->recordFinancingPayment(
                            $fin,
                            (float) $exactAmount,
                            $method,
                            PaymentRecorder::paymongoPaymentId($paidPayment, $sessionAttributes),
                            $trn
                        );
                    } elseif (($sessionAttributes['status'] ?? '') === 'expired') {
                        $fin->update([
                            'paymongo_checkout_id' => null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('FIN Sync error: ' . $e->getMessage());
            }
        }
    }
}
