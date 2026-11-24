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

        $downpayments = $this->downpaymentsForBuyer($buyer, 'dashboard');

        $financing = $downpayments->isNotEmpty()
            ? FinancingRecord::where('buyer_id', $buyer->id)->first()
            : null;

        $calendarEvents = $this->buildPaymentCalendarEvents($downpayments, $financing);

        return view('buyer.dashboard', compact('buyer', 'downpayments', 'financing', 'calendarEvents'));
    }

    public function payments()
    {
        $user = Auth::user();
        $buyer = $user->buyerMasterList;

        if (!$buyer) {
            abort(403, 'No buyer record found for your account.');
        }

        $downpayments = $this->downpaymentsForBuyer($buyer, 'payments');

        $financing = $downpayments->isNotEmpty()
            ? FinancingRecord::where('buyer_id', $buyer->id)->first()
            : null;

        $finHistories = collect();
        if ($financing) {
            $finHistories = \Illuminate\Support\Facades\DB::table('payments')
                ->where('financing_record_id', $financing->id)
                ->whereNull('deleted_at')
                ->orderByDesc('payment_date')
                ->get();
        }

        $calendarEvents = $this->buildPaymentCalendarEvents($downpayments, $financing);

        return view('buyer.payments', compact('buyer', 'downpayments', 'financing', 'finHistories', 'calendarEvents'));
    }

    public function dpHistory()
    {
        $user = Auth::user();
        $buyer = $user->buyerMasterList;

        if (!$buyer) {
            abort(403, 'No buyer record found for your account.');
        }

        $downpayments = $this->downpaymentsForBuyer($buyer, 'history');

        $dpHistories = \Illuminate\Support\Facades\DB::table('payments')
            ->whereIn('downpayment_id', $downpayments->pluck('id'))
            ->whereNull('deleted_at')
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

        $buyer = Auth::user()->buyerMasterList;
        if (!$buyer || !$buyer->reservations()->where('id', $dp->reservation_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($dp->balance <= 0 || $dp->status === 'Fully Paid') {
            return response()->json(['success' => false, 'message' => 'Downpayment is already fully paid.'], 422);
        }

        $principal = min((float) $dp->monthly_amortization, (float) $dp->balance);
        $penalty = $dp->latePenaltyAmount($principal);

        $lineItems = [[
            'currency' => 'PHP',
            'amount' => (int) round($principal * 100),
            'name' => 'Downpayment Amortization',
            'quantity' => 1
        ]];
        if ($penalty > 0) {
            $lineItems[] = [
                'currency' => 'PHP',
                'amount' => (int) round($penalty * 100),
                'name' => 'Late Payment Penalty (' . rtrim(rtrim(number_format((float) $dp->penalty_percentage, 2), '0'), '.') . '%)',
                'quantity' => 1
            ];
        }

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

    /**
     * A cancelled reservation still keeps its downpayment contract. The buyer account must show that schedule.
     */
    private function downpaymentsForBuyer(BuyerMasterList $buyer, string $surface)
    {
        $reservations = $buyer->reservations()
            ->where(function ($query) {
                $query->whereIn('status', ['Reserved', 'Pending', 'Converted'])
                    ->orWhereHas('downpayments');
            })
            ->with('downpayments')
            ->get();

        $downpayments = $reservations->flatMap->downpayments->values();


        return $downpayments;
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
                    'cancel_url' => request()->getSchemeAndHttpHost() . '/buyer/payments?cancelled=true',
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

        $hist = \Illuminate\Support\Facades\DB::table('payments')
            ->where('id', $id)
            ->whereNotNull('downpayment_id')
            ->whereNull('deleted_at')
            ->first();
        if (!$hist) {
            abort(404, 'Payment history not found.');
        }

        // Verify the history belongs to this buyer
        $dp = Downpayment::find($hist->downpayment_id);
        if (!$dp || !$buyer->reservations()->where('id', $dp->reservation_id)->exists()) {
            abort(403, 'Unauthorized access to this receipt.');
        }

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('buyer.receipt_pdf', compact('hist', 'buyer', 'dp'));
        return $pdf->stream('Receipt_DP_' . $hist->id . '.pdf');
    }

    private function buildPaymentCalendarEvents($downpayments, $financing): array
    {
        $events = [];

        if ($downpayments->isNotEmpty()) {
            $paidRows = \Illuminate\Support\Facades\DB::table('payments')
                ->whereIn('downpayment_id', $downpayments->pluck('id'))
                ->whereNull('deleted_at')
                ->orderBy('payment_date')
                ->get();

            foreach ($paidRows as $p) {
                $dt = $p->payment_date ?: $p->created_at;
                if (!$dt) continue;
                $events[] = [
                    'id' => 'dp-paid-' . $p->id,
                    'title' => '✓ Paid • ₱' . number_format((float) $p->amount_paid, 2),
                    'start' => \Carbon\Carbon::parse($dt)->format('Y-m-d'),
                    'url' => route('buyer.payments.dp.history'),
                    'backgroundColor' => '#dcfce7',
                    'borderColor' => '#10b981',
                    'textColor' => '#065f46',
                ];
            }

            foreach ($downpayments as $dp) {
                if ((float) $dp->balance <= 0 || !$dp->due_date) {
                    continue;
                }
                $monthly = (float) $dp->monthly_amortization > 0 ? (float) $dp->monthly_amortization : (float) $dp->balance;
                $remaining = (float) $dp->balance;
                $due = \Carbon\Carbon::parse($dp->due_date);

                for ($m = 0; $m < 12 && $remaining > 0.01; $m++) {
                    $instAmt = min($monthly, $remaining);
                    $instDate = $due->copy()->addMonthsNoOverflow($m);
                    $isCurrent = ($m === 0);
                    $isOverdue = $isCurrent && $instDate->isPast() && !$instDate->isToday();

                    if ($isCurrent) {
                        $instAmt += (float) $dp->latePenaltyAmount($instAmt);
                    }

                    $events[] = [
                        'id' => 'dp-due-' . $dp->id . '-' . $m,
                        'title' => ($isOverdue ? '⚠ Overdue' : ($isCurrent ? '📅 Due Now' : '🗓 Installment')) . ' • ₱' . number_format($instAmt, 2),
                        'start' => $instDate->format('Y-m-d'),
                        'url' => route('buyer.payments'),
                        'backgroundColor' => $isOverdue ? '#fee2e2' : ($isCurrent ? '#fef3c7' : '#e0e7ff'),
                        'borderColor' => $isOverdue ? '#ef4444' : ($isCurrent ? '#f59e0b' : '#4f46e5'),
                        'textColor' => $isOverdue ? '#991b1b' : ($isCurrent ? '#92400e' : '#3730a3'),
                    ];

                    $remaining -= min($monthly, $remaining);
                }
            }
        }

        if ($financing && (float) $financing->balance > 0 && !empty($financing->due_date)) {
            $finDue = \Carbon\Carbon::parse($financing->due_date);
            $isOverdue = $finDue->isPast() && !$finDue->isToday();
            $events[] = [
                'id' => 'fin-due-' . $financing->id,
                'title' => '🏦 Shortfall Due • ₱' . number_format((float) $financing->balance, 2),
                'start' => $finDue->format('Y-m-d'),
                'url' => route('buyer.payments'),
                'backgroundColor' => $isOverdue ? '#fee2e2' : '#fef3c7',
                'borderColor' => $isOverdue ? '#ef4444' : '#f59e0b',
                'textColor' => $isOverdue ? '#991b1b' : '#92400e',
            ];
        }

        return $events;
    }
}
