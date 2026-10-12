<?php

namespace App\Http\Controllers;

use App\Models\FinancingRecord;
use App\Models\BuyerMasterList;
use App\Enums\FinancingStatus;
use App\Services\PaymentRecorder;
use Illuminate\Http\Request;

class FinancingRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = FinancingRecord::with(['buyerMasterList', 'lot']);

        // Filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('buyerMasterList.user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $records = $query->get();

        // Graphs Data
        $statusCounts = [
            'Pending' => FinancingRecord::where('status', 'Pending')->count(),
            'Processing' => FinancingRecord::where('status', 'Processing')->count(),
            'Released' => FinancingRecord::where('status', 'Released')->count(),
            'Cleared' => FinancingRecord::where('status', 'Cleared')->count(),
        ];

        $totalReceivables = FinancingRecord::sum('receivables');
        $totalPaid = FinancingRecord::sum('amount_paid');
        $totalBalances = FinancingRecord::sum('balance');

        return view('admin.financing.index', compact('records', 'statusCounts', 'totalReceivables', 'totalPaid', 'totalBalances'));
    }

    public function create()
    {
        $buyersWithFinancing = FinancingRecord::pluck('buyer_id')->toArray();
        $buyers = BuyerMasterList::whereNotIn('id', $buyersWithFinancing)
            ->with('reservations.lot')
            ->get();
            
        return view('admin.financing.create', compact('buyers'));
    }

    public function store(Request $request)
    {
        // Generate from Downpayment / Buyer Info
        // Simplified store logic for now
        $buyer = BuyerMasterList::with(['reservations.lot', 'lot'])->findOrFail($request->buyer_id);
        $lotId = $buyer->lot_id ?? $buyer->reservations->first()?->lot_id;

        if (!$lotId) {
            return back()->with('error', 'Buyer has no lot assigned.');
        }

        if (FinancingRecord::where('buyer_id', $buyer->id)->exists()) {
            return back()->with('error', 'Buyer already has an active financing record. Duplication is not allowed.');
        }

        FinancingRecord::create([
            'buyer_id' => $buyer->id,
            'lot_id' => $lotId,
            'status' => FinancingStatus::Pending->value,
        ]);

        return redirect()->route('financing.index')->with('success', 'Financing Record Created.');
    }

    public function show($id)
    {
        $record = FinancingRecord::with(['buyerMasterList', 'lot'])->findOrFail($id);
        return view('admin.financing.show', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = FinancingRecord::findOrFail($id);

        $request->validate([
            'status' => ['nullable', \Illuminate\Validation\Rule::in(FinancingStatus::values())],
            'financing_method' => 'nullable|string|max:50',
            'loan_base' => 'nullable|numeric|min:0',
            'loan_term' => 'nullable|integer|min:0|max:100',
            'monthly_amortization' => 'nullable|numeric|min:0',
            'paid_by_vendee_equity' => 'nullable|numeric|min:0',
        ]);
        
        $data = $request->all();
        foreach (['loan_base', 'monthly_amortization', 'paid_by_vendee_equity'] as $moneyField) {
            if (! isset($data[$moneyField]) || $data[$moneyField] === '') {
                $data[$moneyField] = 0;
            }
        }
        if (($data['loan_term'] ?? '') === '') {
            $data['loan_term'] = null;
        }
        if (($data['financing_method'] ?? '') === '') {
            $data['financing_method'] = null;
        }
        
        // Calculate Base Financials
        $contractPrice = $data['contract_price'] ?? $record->contract_price;
        $mbrdcAddFees = $data['mbrdc_add_fees'] ?? $record->mbrdc_add_fees;
        $otherExp = $data['other_exp_annotation'] ?? $record->other_exp_annotation;
        $improvementsFee = $data['improvements_fee'] ?? $record->improvements_fee;
        $equity = $data['paid_by_vendee_equity'] ?? $record->paid_by_vendee_equity;
        
        $data['total_consideration'] = $contractPrice + $mbrdcAddFees + $otherExp + $improvementsFee;
        $data['mbrdc_amt_due_for_financing'] = $data['total_consideration'] - $equity;

        // Retention/Conversion is only deducted while it is on hold (Pag-IBIG/bank hold)
        $retentionOnHold = filter_var($request->input('retention_on_hold', $record->retention_on_hold), FILTER_VALIDATE_BOOLEAN);
        $data['retention_on_hold'] = $retentionOnHold;

        // Auto Calculate Totals
        $totalDeductions = 
            ($data['inspection_fee'] ?? 0) + 
            ($retentionOnHold ? ($data['retention_fee'] ?? 0) : 0) +
            ($data['sri_mri'] ?? 0) +
            ($data['pag_ibig_non_life'] ?? 0) +
            ($data['interim_mri'] ?? 0);
            
        $data['total_amt_due'] = $totalDeductions;
        
        if (isset($data['loan_release'])) {
            $data['net_loan_proceeds'] = $data['loan_release'] - $totalDeductions;
            // Balance = total amount for financing minus gross loan released by Pag-IBIG/bank
            $data['receivables'] = max(0, $data['mbrdc_amt_due_for_financing'] - $data['loan_release'] + ($data['additional_bill_of_materials'] ?? 0) + ($data['mbrdc_turn_over_fee'] ?? 0));
            $data['balance'] = max(0, $data['receivables'] - $record->amount_paid);
        }

        if (isset($data['status']) && $data['status'] == FinancingStatus::Released->value && !$record->loan_release_date) {
            $data['loan_release_date'] = now();
        }

        $record->update($data);
        return redirect()->route('financing.show', $id)->with('success', 'Updated Successfully');
    }
    
    public function recordPayment(Request $request, $id, PaymentRecorder $recorder)
    {
        $record = FinancingRecord::findOrFail($id);
        $request->validate(['amount' => 'required|numeric|min:0.01']);

        $recorder->recordFinancingPayment($record, (float) $request->amount, 'Office Payment');

        return back()->with('success', 'Payment Recorded');
    }
}
