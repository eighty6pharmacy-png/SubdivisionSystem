<?php

namespace App\Http\Controllers;

use App\Models\FinancingRecord;
use App\Models\BuyerMasterList;
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
            $query->whereHas('buyerMasterList', function($q) use ($search) {
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
        $buyersWithFinancing = FinancingRecord::pluck('buyer_master_list_id')->toArray();
        $buyers = BuyerMasterList::whereNotIn('id', $buyersWithFinancing)
            ->with('reservations.lot')
            ->get();
            
        return view('admin.financing.create', compact('buyers'));
    }

    public function store(Request $request)
    {
        // Generate from Downpayment / Buyer Info
        // Simplified store logic for now
        $buyer = BuyerMasterList::findOrFail($request->buyer_id);
        $lot = $buyer->reservations->first()->lot ?? null;

        if (!$lot) {
            return back()->with('error', 'Buyer has no lot reserved.');
        }

        if (FinancingRecord::where('buyer_master_list_id', $buyer->id)->exists()) {
            return back()->with('error', 'Buyer already has an active financing record. Duplication is not allowed.');
        }

        FinancingRecord::create([
            'buyer_master_list_id' => $buyer->id,
            'lot_id' => $lot->id,
            'contract_price' => $buyer->contract_amount ?? 0,
            'total_consideration' => $buyer->contract_amount ?? 0,
            'paid_by_vendee_equity' => $buyer->equity ?? 0,
            'mbrdc_amt_due_for_financing' => ($buyer->contract_amount ?? 0) - ($buyer->equity ?? 0),
            'status' => 'Pending'
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
        
        $data = $request->all();
        
        // Calculate Base Financials
        $contractPrice = $data['contract_price'] ?? $record->contract_price;
        $mbrdcAddFees = $data['mbrdc_add_fees'] ?? $record->mbrdc_add_fees;
        $otherExp = $data['other_exp_annotation'] ?? $record->other_exp_annotation;
        $improvementsFee = $data['improvements_fee'] ?? $record->improvements_fee;
        
        $data['total_consideration'] = $contractPrice + $mbrdcAddFees + $otherExp + $improvementsFee;
        $data['mbrdc_amt_due_for_financing'] = $data['total_consideration'] - $record->paid_by_vendee_equity;

        // Auto Calculate Totals
        $totalDeductions = 
            ($data['inspection_fee'] ?? 0) + 
            ($data['retention_fee'] ?? 0) +
            ($data['sri_mri'] ?? 0) +
            ($data['pag_ibig_non_life'] ?? 0) +
            ($data['interim_mri'] ?? 0);
            
        $data['total_amt_due'] = $totalDeductions;
        
        if (isset($data['loan_release'])) {
            $data['net_loan_proceeds'] = $data['loan_release'] - $totalDeductions;
            $data['receivables'] = $data['mbrdc_amt_due_for_financing'] - $data['net_loan_proceeds'] + ($data['additional_bill_of_materials'] ?? 0) + ($data['mbrdc_turn_over_fee'] ?? 0);
            $data['balance'] = max(0, $data['receivables'] - $record->amount_paid);
        }

        if (isset($data['status']) && $data['status'] == 'Released' && !$record->loan_release_date) {
            $data['loan_release_date'] = now();
        }

        $record->update($data);
        return redirect()->route('financing.show', $id)->with('success', 'Updated Successfully');
    }
    
    public function recordPayment(Request $request, $id)
    {
        $record = FinancingRecord::findOrFail($id);
        $amount = $request->amount;
        
        $record->amount_paid += $amount;
        $record->balance = max(0, $record->receivables - $record->amount_paid);
        
        if ($record->balance <= 0 && $record->status == 'Released') {
            $record->status = 'Cleared';
        }
        
        $record->save();
        return back()->with('success', 'Payment Recorded');
    }
}
