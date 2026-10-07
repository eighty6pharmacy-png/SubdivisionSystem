<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncPaymongoCheckouts
{
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

    private function syncResidentBills($user)
    {
        $pendingBills = \App\Models\UtilityBill::where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->whereNotNull('paymongo_checkout_id')
            ->get();
            
        foreach ($pendingBills as $bill) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'accept' => 'application/json',
                    'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
                ])->get('https://api.paymongo.com/v1/checkout_sessions/' . $bill->paymongo_checkout_id);
                
                if ($response->successful()) {
                    $sessionData = $response->json();
                    $payments = $sessionData['data']['attributes']['payments'] ?? [];
                    
                    $paidPayment = collect($payments)->first(function ($payment) {
                        return $payment['attributes']['status'] === 'paid';
                    });
                    
                    if ($paidPayment) {
                        $sourceType = $paidPayment['attributes']['source']['type'] ?? '';
                        $method = $sourceType === 'gcash' ? 'GCash' : ($sourceType ? ucfirst($sourceType) : 'PayMongo');
                        $trn = $paidPayment['attributes']['payment_intent_id'] ?? ('PM-' . strtoupper(\Illuminate\Support\Str::random(8)));
                        $trn .= '-' . substr($bill->id, 0, 8);
                        $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : ($bill->amount + $bill->previous_balance);
                        $paidAt = isset($paidPayment['attributes']['paid_at']) ? \Carbon\Carbon::createFromTimestamp($paidPayment['attributes']['paid_at'], config('app.timezone')) : now();
                        
                        \App\Models\Payment::create([
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'utility_bill_id' => $bill->id,
                            'amount_paid' => $exactAmount,
                            'method' => $method,
                            'trn' => $trn,
                            'payment_date' => $paidAt,
                        ]);
                        $bill->update(['status' => 'paid']);
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
            $q->where('buyer_master_list_id', $buyer->id);
        })->where('balance', '>', 0)
          ->whereNotNull('paymongo_checkout_id')
          ->get();

        foreach ($pendingDps as $dp) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'accept' => 'application/json',
                    'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
                ])->get('https://api.paymongo.com/v1/checkout_sessions/' . $dp->paymongo_checkout_id);

                if ($response->successful()) {
                    $sessionData = $response->json();
                    $payments = $sessionData['data']['attributes']['payments'] ?? [];
                    $paidPayment = collect($payments)->first(function ($payment) {
                        return $payment['attributes']['status'] === 'paid';
                    });

                    if ($paidPayment) {
                        $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : $dp->monthly_amortization;
                        $dp->amount += $exactAmount;
                        $dp->balance -= $exactAmount;
                        if ($dp->balance <= 0) {
                            $dp->status = 'Fully Paid';
                            $dp->balance = 0;
                        } else {
                            $dp->status = 'Good Standing';
                        }
                        $dp->due_date = \Carbon\Carbon::parse($dp->due_date)->addMonth();
                        $dp->paymongo_checkout_id = null;
                        $dp->save();

                        $sourceType = $paidPayment['attributes']['source']['type'] ?? '';
                        $method = $sourceType === 'gcash' ? 'GCash' : ($sourceType ? ucfirst($sourceType) : 'Online Payment');

                        $trn = 'PM-' . strtoupper(\Illuminate\Support\Str::random(8)) . '-' . substr($dp->id, 0, 8);
                        \Illuminate\Support\Facades\DB::table('downpayment_histories')->insert([
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'downpayment_id' => $dp->id,
                            'amount' => $exactAmount,
                            'payment_date' => now(),
                            'trn' => $trn,
                            'payment_method' => $method,
                            'status' => 'Paid',
                            'created_at' => now(),
                            'updated_at' => now()
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
        $fin = \App\Models\FinancingRecord::where('buyer_master_list_id', $buyer->id)
            ->where('balance', '>', 0)
            ->whereNotNull('paymongo_checkout_id')
            ->first();

        if ($fin) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'accept' => 'application/json',
                    'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
                ])->get('https://api.paymongo.com/v1/checkout_sessions/' . $fin->paymongo_checkout_id);

                if ($response->successful()) {
                    $sessionData = $response->json();
                    $payments = $sessionData['data']['attributes']['payments'] ?? [];
                    $paidPayment = collect($payments)->first(function ($payment) {
                        return $payment['attributes']['status'] === 'paid';
                    });

                    if ($paidPayment) {
                        $exactAmount = isset($paidPayment['attributes']['amount']) ? ($paidPayment['attributes']['amount'] / 100) : $fin->balance;
                        $fin->amount_paid += $exactAmount;
                        $fin->balance = max(0, $fin->receivables - $fin->amount_paid);
                        $fin->paymongo_checkout_id = null;
                        $fin->save();
                        
                        \Illuminate\Support\Facades\DB::table('financing_payment_histories')->insert([
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'financing_record_id' => $fin->id,
                            'amount_paid' => $exactAmount,
                            'payment_date' => now(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('FIN Sync error: ' . $e->getMessage());
            }
        }
    }
}
