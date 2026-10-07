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

        // Fetch downpayments for Reserved/Active/Converted reservations
        $reservations = $buyer->reservations()->whereIn('status', ['Reserved', 'Pending', 'Converted'])->with('downpayments')->get();
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

        $reservations = $buyer->reservations()->whereIn('status', ['Reserved', 'Pending', 'Converted'])->with('downpayments')->get();
        $downpayments = collect();
        foreach ($reservations as $res) {
            foreach ($res->downpayments as $dp) {
                $downpayments->push($dp);
            }
        }

        $financing = FinancingRecord::where('buyer_master_list_id', $buyer->id)->first();

        $finHistories = collect();
        if ($financing) {
            $finHistories = \Illuminate\Support\Facades\DB::table('financing_payment_histories')
                ->where('financing_record_id', $financing->id)
                ->orderByDesc('payment_date')
                ->get();
        }

        return view('buyer.payments', compact('buyer', 'downpayments', 'financing', 'finHistories'));
    }

    public function dpHistory()
    {
        $user = Auth::user();
        $buyer = $user->buyerMasterList;

        if (!$buyer) {
            abort(403, 'No buyer record found for your account.');
        }

        $reservations = $buyer->reservations()->whereIn('status', ['Reserved', 'Pending', 'Converted'])->with('downpayments')->get();
        $downpayments = collect();
        foreach ($reservations as $res) {
            foreach ($res->downpayments as $dp) {
                $downpayments->push($dp);
            }
        }

        $dpHistories = \Illuminate\Support\Facades\DB::table('downpayment_histories')
            ->whereIn('downpayment_id', $downpayments->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->get();
            
        // Map due dates based on chronological order
        foreach ($downpayments as $dp) {
            $dpHistoriesForDp = $dpHistories->where('downpayment_id', $dp->id);
            $N = $dpHistoriesForDp->count();
            $currentDueDate = $dp->due_date ? \Carbon\Carbon::parse($dp->due_date) : null;
            
            $index = 0;
            foreach ($dpHistoriesForDp as $hist) {
                if ($currentDueDate) {
                    $hist->due_date_str = $currentDueDate->copy()->subMonths($N - $index)->format('M d, Y');
                } else {
                    $hist->due_date_str = 'N/A';
                }
                $index++;
            }
        }
        
        // Sort back to descending for the view
        $dpHistories = $dpHistories->sortByDesc('payment_date')->values();

        return view('buyer.dp_history', compact('buyer', 'dpHistories'));
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

        $buyer = Auth::user()->buyerMasterList;
        if (!$buyer || $fin->buyer_master_list_id !== $buyer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }
        if ($fin->balance <= 0) {
            return response()->json(['success' => false, 'message' => 'No remaining balance to pay.'], 422);
        }

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
                    'success_url' => request()->getSchemeAndHttpHost() . '/buyer/payments?payment=success',
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

    public function downloadDpReceipt(Request $request, $id)
    {
        $user = Auth::user();
        $buyer = $user->buyerMasterList;

        if (!$buyer) {
            abort(403, 'No buyer record found for your account.');
        }

        $hist = \Illuminate\Support\Facades\DB::table('downpayment_histories')->where('id', $id)->first();
        if (!$hist) {
            abort(404, 'Payment history not found.');
        }

        // Verify the history belongs to this buyer
        $dp = Downpayment::find($hist->downpayment_id);
        if (!$dp || !$buyer->reservations()->where('id', $dp->reservation_id)->exists()) {
            abort(403, 'Unauthorized access to this receipt.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('buyer.receipt_pdf', compact('hist', 'buyer', 'dp'));
        return $pdf->stream('Receipt_DP_' . $hist->id . '.pdf');
    }
}
