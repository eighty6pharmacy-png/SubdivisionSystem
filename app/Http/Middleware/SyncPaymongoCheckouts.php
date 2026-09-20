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
        if (auth()->check() && auth()->user()->hasRole('Resident')) {
            $pendingBills = \App\Models\UtilityBill::where('user_id', auth()->id())
                ->where('status', '!=', 'paid')
                ->whereNotNull('paymongo_checkout_id')
                ->get();
                
            foreach ($pendingBills as $bill) {
                try {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'accept' => 'application/json',
                        'authorization' => 'Basic ' . base64_encode(env('PAYMONGO_SECRET_KEY') . ':')
                    ])->get('https://api.paymongo.com/v1/checkout_sessions/' . $bill->paymongo_checkout_id);
                    
                    if ($response->successful()) {
                        $sessionData = $response->json();
                        $payments = $sessionData['data']['attributes']['payments'] ?? [];
                        
                        // If any payment inside the session is 'paid'
                        $paidPayment = collect($payments)->first(function ($payment) {
                            return $payment['attributes']['status'] === 'paid';
                        });
                        
                        if ($paidPayment) {
                            $sourceType = $paidPayment['attributes']['source']['type'] ?? '';
                            if ($sourceType === 'gcash') {
                                $method = 'GCash';
                            } else {
                                $method = $sourceType ? ucfirst($sourceType) : 'PayMongo';
                            }
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

        return $next($request);
    }
}
