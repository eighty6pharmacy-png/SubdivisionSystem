<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BuyerMasterList;
use App\Models\Downpayment;
use App\Models\FinancingRecord;

class BuyerPortalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $buyer = $user->buyerMasterList;

        if (!$buyer) {
            abort(403, 'No buyer record found for your account.');
        }

        // Fetch downpayments for Reserved/Active reservations
        $reservations = $buyer->reservations()->whereIn('status', ['Reserved', 'Pending'])->with('downpayments')->get();
        $downpayments = collect();
        foreach ($reservations as $res) {
            foreach ($res->downpayments as $dp) {
                $downpayments->push($dp);
            }
        }

        // Fetch financing record
        $financing = FinancingRecord::where('buyer_master_list_id', $buyer->id)->first();

        return view('buyer.dashboard', compact('buyer', 'downpayments', 'financing'));
    }

    public function payments()
    {
        $user = Auth::user();
        $buyer = $user->buyerMasterList;

        if (!$buyer) {
            abort(403, 'No buyer record found for your account.');
        }

        $reservations = $buyer->reservations()->whereIn('status', ['Reserved', 'Pending'])->with('downpayments')->get();
        $downpayments = collect();
        foreach ($reservations as $res) {
            foreach ($res->downpayments as $dp) {
                $downpayments->push($dp);
            }
        }

        $financing = FinancingRecord::where('buyer_master_list_id', $buyer->id)->first();

        $dpHistories = \Illuminate\Support\Facades\DB::table('downpayment_histories')
            ->whereIn('downpayment_id', $downpayments->pluck('id'))
            ->orderByDesc('payment_date')
            ->get();
            
        $finHistories = collect();
        if ($financing) {
            $finHistories = \Illuminate\Support\Facades\DB::table('financing_payment_histories')
                ->where('financing_record_id', $financing->id)
                ->orderByDesc('payment_date')
                ->get();
        }

        return view('buyer.payments', compact('buyer', 'downpayments', 'financing', 'dpHistories', 'finHistories'));
    }

    public function payDownpayment(Request $request)
    {
        $dp = Downpayment::find($request->input('dp_id'));
        if (!$dp) return response()->json(['success' => false, 'message' => 'Downpayment not found.'], 404);

        $amount = $dp->monthly_amortization;
        if ($dp->balance < $amount) $amount = $dp->balance;

        $lineItems = [[
            'currency' => 'PHP',
            'amount' => (int) round($amount * 100),
            'name' => 'Downpayment Amortization',
            'quantity' => 1
        ]];

        return $this->createCheckoutSession($lineItems, 'DP-' . $dp->id, 'Subdivision Downpayment', $dp);
    }

    public function payFinancing(Request $request)
    {
        $fin = FinancingRecord::find($request->input('fin_id'));
        if (!$fin) return response()->json(['success' => false, 'message' => 'Financing record not found.'], 404);

        $lineItems = [[
            'currency' => 'PHP',
            'amount' => (int) round($fin->balance * 100),
            'name' => 'Financing Shortfall Balance',
            'quantity' => 1
        ]];

        return $this->createCheckoutSession($lineItems, 'FIN-' . $fin->id, 'Subdivision Financing Shortfall', $fin);
    }

    private function createCheckoutSession($lineItems, $reference, $description, $record = null)
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'authorization' => 'Basic ' . base64_encode(config('services.paymongo.secret_key') . ':')
        ])->post('https://api.paymongo.com/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'line_items' => $lineItems,
                    'payment_method_types' => ['gcash', 'paymaya', 'card', 'grab_pay', 'qrph'],
                    'success_url' => request()->getSchemeAndHttpHost() . '/buyer/dashboard?payment=success',
                    'description' => $description,
                    'reference_number' => $reference
                ]
            ]
        ]);

        if ($response->successful()) {
            $checkoutId = $response->json()['data']['id'] ?? null;
            if ($checkoutId && $record) {
                $record->paymongo_checkout_id = $checkoutId;
                $record->save();
            }
            return response()->json(['success' => true, 'checkout_url' => $response->json()['data']['attributes']['checkout_url']]);
        }

        return response()->json(['success' => false, 'message' => 'Failed to connect to payment gateway.'], 500);
    }
}
