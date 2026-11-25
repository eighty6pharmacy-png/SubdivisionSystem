@extends('layouts.admin')
@section('title', 'Manage Financing Record')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}?v={{ filemtime(public_path('css/admin-finance.css')) }}">
<style>
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    .form-control:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .card-section {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .card-section-header {
        background: #f8fafc;
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-section-body {
        padding: 24px;
    }
    .list-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 14px;
    }
    .list-item:last-child {
        border-bottom: none;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        background: #1e293b;
        color: #fff;
    }
    .btn-submit {
        background: #3b82f6;
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
    }
    .btn-submit:hover {
        background: #2563eb;
    }
</style>

<div class="bill-container fade-in">
    <div class="bill-header">
        <div class="bill-title">
            <h1>Loan & Additional Fees Dashboard - {{ $record->buyerMasterList->first_name ?? '' }} {{ $record->buyerMasterList->last_name ?? 'Buyer' }}</h1>
        </div>
        <div class="bill-actions">
            <a href="{{ route('financing.index') }}" class="pay-button" style="background: #e2e8f0; color: #475569; text-decoration: none;">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; border-left: 4px solid #059669; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div style="background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="responsive-grid grid-2-1">
        <!-- 1. Base Financials & Update Form -->
        <div class="card-section">
            <div class="card-section-header">
                <span>Update Loan & Deductions</span>
                <span class="status-badge">{{ $record->status }}</span>
            </div>
            <div class="card-section-body">
                <form method="POST" action="{{ route('financing.update', $record->id) }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="responsive-grid grid-2" style="gap: 24px;">
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">Base Information & Add-ons</h4>
                            <div class="form-group">
                                <label>Financing Method</label>
                                <select name="financing_method" id="financingMethod" class="form-control">
                                    <option value="">-- Select Method --</option>
                                    <option value="Pag-IBIG" {{ $record->financing_method == 'Pag-IBIG' ? 'selected' : '' }}>Pag-IBIG</option>
                                    <option value="Bank Loan" {{ $record->financing_method == 'Bank Loan' ? 'selected' : '' }}>Bank Loan</option>
                                    <option value="Cash" {{ $record->financing_method == 'Cash' ? 'selected' : '' }}>Cash</option>
                                </select>
                            </div>
                            <div class="form-group"><label>Contract Price</label><input type="number" step="0.01" name="contract_price" class="form-control" value="{{ $record->contract_price }}"></div>
                            <div class="form-group"><label>Equity Paid by Buyer</label><input type="number" step="0.01" name="paid_by_vendee_equity" class="form-control" value="{{ $record->paid_by_vendee_equity }}"></div>
                            <div class="form-group"><label>MBRDC Add. Fees</label><input type="number" step="0.01" name="mbrdc_add_fees" class="form-control" value="{{ $record->mbrdc_add_fees }}"></div>
                            <div class="form-group"><label>Other Exp (Annotation/DA/SPA)</label><input type="number" step="0.01" name="other_exp_annotation" class="form-control" value="{{ $record->other_exp_annotation }}"></div>
                            <div class="form-group"><label>Improvements Fee</label><input type="number" step="0.01" name="improvements_fee" class="form-control" value="{{ $record->improvements_fee }}"></div>
                            <div class="form-group"><label>Turn Over Fees</label><input type="number" step="0.01" name="mbrdc_turn_over_fee" class="form-control" value="{{ $record->mbrdc_turn_over_fee }}"></div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="Pending" {{ $record->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="Processing" {{ $record->status == 'Processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="Released" {{ $record->status == 'Released' ? 'selected' : '' }}>Released (Triggers Date)</option>
                                    <option value="Cleared" {{ $record->status == 'Cleared' ? 'selected' : '' }}>Cleared</option>
                                </select>
                            </div>
                        </div>
                        
                        <div id="loanFields" style="{{ $record->financing_method === 'Cash' ? 'display:none' : '' }}">
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">&nbsp;</div>
                            <div class="form-group"><label>Gross Loan Released</label><input type="number" step="0.01" name="loan_release" class="form-control" style="border-color: #3b82f6;" value="{{ $record->loan_release }}"></div>
                            <div class="form-group"><label>Loan Base</label><input type="number" step="0.01" name="loan_base" class="form-control" value="{{ $record->loan_base }}"></div>
                            <div class="form-group"><label>Loan Term (Years)</label><input type="number" name="loan_term" min="0" max="100" step="1" class="form-control" value="{{ $record->loan_term }}"></div>
                            <div class="form-group"><label>Monthly Amortization</label><input type="number" step="0.01" name="monthly_amortization" class="form-control" value="{{ $record->monthly_amortization }}"></div>
                            <div class="form-group"><label>Inspection/Processing Fee</label><input type="number" step="0.01" name="inspection_fee" class="form-control" style="color: #dc2626;" value="{{ $record->inspection_fee }}"></div>
                            <div class="form-group"><label>Retention/Conversion</label><input type="number" step="0.01" name="retention_fee" class="form-control" style="color: #dc2626;" value="{{ $record->retention_fee }}"></div>
                            <div class="form-group"><label>Retention/Conversion Status</label>
                                <select name="retention_on_hold" id="retentionOnHold" class="form-control">
                                    <option value="1" {{ ($record->retention_on_hold ?? true) ? 'selected' : '' }}>On Hold (deducted from net proceeds)</option>
                                    <option value="0" {{ ($record->retention_on_hold ?? true) ? '' : 'selected' }}>Unheld / Released (not deducted)</option>
                                </select>
                            </div>
                            <div class="form-group"><label>SRI/MRI</label><input type="number" step="0.01" name="sri_mri" class="form-control" style="color: #dc2626;" value="{{ $record->sri_mri }}"></div>
                            <div class="form-group"><label>Pag-Ibig Non Life</label><input type="number" step="0.01" name="pag_ibig_non_life" class="form-control" style="color: #dc2626;" value="{{ $record->pag_ibig_non_life }}"></div>
                            <div class="form-group"><label>Interim MRI</label><input type="number" step="0.01" name="interim_mri" class="form-control" style="color: #dc2626;" value="{{ $record->interim_mri }}"></div>
                            <div class="form-group"><label>Net Loan Proceeds</label><input type="number" step="0.01" name="net_loan_proceeds" class="form-control" style="background-color: #f1f5f9; font-weight: bold; color: #059669;" value="{{ $record->net_loan_proceeds }}" readonly title="Auto-calculated (Gross Loan - Total Deductions)"></div>
                        </div>
                    </div>
                    <div style="margin-top: 24px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                        <button type="submit" class="btn-submit">Save & Auto-Calculate</button>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <!-- Math Summary -->
            <div class="card-section">
                <div class="card-section-header" style="background: #0f172a; color: #fff;">
                    &nbsp;
                </div>
                <div class="card-section-body" style="background: #f8fafc;">
                    <div class="list-item">
                        <span style="color:#64748b;">Total Consideration</span>
                        <span style="font-weight:700;">₱{{ number_format($record->total_consideration, 2) }}</span>
                    </div>
                    <div class="list-item">
                        <span style="color:#64748b;">Equity</span>
                        <span style="font-weight:700; color:#059669;">- ₱{{ number_format($record->paid_by_vendee_equity, 2) }}</span>
                    </div>
                    <div class="list-item" style="background: #e2e8f0; margin: 0 -24px; padding: 12px 24px;">
                        <strong style="color:#0f172a;">Due for Financing</strong>
                        <strong style="color:#0f172a;">₱{{ number_format($record->mbrdc_amt_due_for_financing, 2) }}</strong>
                    </div>
                    <div class="list-item loan-only" style="margin-top: 12px; {{ $record->financing_method === 'Cash' ? 'display:none;' : '' }}">
                        <span style="color:#64748b;">Gross Loan Release</span>
                        <span style="font-weight:700; color:#3b82f6;">₱{{ number_format($record->loan_release, 2) }}</span>
                    </div>
                    <div class="list-item loan-only" style="{{ $record->financing_method === 'Cash' ? 'display:none;' : '' }}">
                        <span style="color:#64748b;">Total Loan Deductions</span>
                        <span style="font-weight:700; color:#dc2626;">- ₱{{ number_format($record->total_amt_due, 2) }}</span>
                    </div>
                    <div class="list-item loan-only" style="background: #dcfce7; margin: 0 -24px -24px -24px; padding: 16px 24px; border-radius: 0 0 16px 16px; {{ $record->financing_method === 'Cash' ? 'display:none;' : '' }}">
                        <strong style="color:#065f46;">Net Loan Proceeds (Dev Gets)</strong>
                        <strong style="color:#059669; font-size: 16px;">₱{{ number_format($record->net_loan_proceeds, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Shortfall / Receivables -->
            <div class="card-section" style="border-color: #fca5a5;">
                <div class="card-section-header" style="background: #fef2f2; color: #991b1b; border-color: #fca5a5;">
                    Receivables
                </div>
                <div class="card-section-body" style="text-align: center;">
                    <div class="loan-only" style="{{ $record->financing_method === 'Cash' ? 'display:none;' : '' }}">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Retention/Conversion Hold</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 4px;">₱{{ number_format($record->retention_fee, 2) }}</div>
                    @php $isHeld = (bool) ($record->retention_on_hold ?? true); @endphp
                    <div style="font-size: 12px; font-weight: 700; margin-bottom: 8px; color: {{ $isHeld ? '#b45309' : '#059669' }};">{{ $isHeld ? 'ON HOLD (Pag-IBIG/Bank)' : 'UNHELD / RELEASED' }}</div>
                    <button type="button" onclick="toggleRetentionHold()" class="btn-submit" style="width: 100%; margin-bottom: 16px; background: {{ $isHeld ? '#059669' : '#b45309' }};">{{ $isHeld ? 'Unhold' : 'Hold Again' }}</button>
                    </div>
                    
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Remaining Balance</div>
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 24px; color: {{ $record->balance > 0 ? '#dc2626' : '#059669' }}">₱{{ number_format($record->balance, 2) }}</div>
                    
                    @if($record->balance > 0)
                        <button onclick="document.getElementById('paymentModal').style.display='flex';" class="btn-submit" style="width: 100%; background: #10b981;">Record Buyer Payment</button>
                    @else
                        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 14px;">Balance Fully Paid</div>
                    @endif
                </div>
            </div>

            <!-- Payment Record Button (below Receivables box, outside the box) -->
            <button type="button" onclick="document.getElementById('paymentRecordsModal').style.display='flex';" class="btn-submit" style="width: 100%; background: #0f172a; margin-bottom: 24px;">
                Payment Record ({{ $record->payments->count() }})
            </button>
        </div>
    </div>
</div>

<!-- Payment Records Modal -->
<div id="paymentRecordsModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3000;">
    <div class="bill-modal-content" style="max-width: 880px; width: 95%; padding: 32px; border-radius: 24px; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">Payment Records</h2>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">
                    {{ $record->buyerMasterList->first_name ?? '' }} {{ $record->buyerMasterList->last_name ?? 'Buyer' }}
                    • Block {{ $record->lot->block ?? 'N/A' }}, Lot {{ $record->lot->lot_number ?? 'N/A' }}
                </p>
            </div>
            <button type="button" onclick="document.getElementById('paymentRecordsModal').style.display='none';" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Total Receivables</div>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px;">₱{{ number_format((float) $record->receivables, 2) }}</div>
            </div>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #166534;">Total Amount Paid</div>
                <div style="font-size: 20px; font-weight: 800; color: #059669; margin-top: 4px;">₱{{ number_format((float) $record->amount_paid, 2) }}</div>
            </div>
            <div style="background: {{ $record->balance > 0 ? '#fef2f2' : '#f0fdf4' }}; border: 1px solid {{ $record->balance > 0 ? '#fecaca' : '#bbf7d0' }}; border-radius: 12px; padding: 14px 16px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: {{ $record->balance > 0 ? '#991b1b' : '#166534' }};">Remaining Balance</div>
                <div style="font-size: 20px; font-weight: 800; color: {{ $record->balance > 0 ? '#dc2626' : '#059669' }}; margin-top: 4px;">₱{{ number_format((float) $record->balance, 2) }}</div>
            </div>
        </div>

        <div class="bill-table-container" style="overflow-x: auto;">
            <table class="bill-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">#</th>
                        <th style="padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Reference / TRN</th>
                        <th style="padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Payment Date</th>
                        <th style="padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Method</th>
                        <th style="padding: 12px 14px; text-align: center; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Status</th>
                        <th style="padding: 12px 14px; text-align: right; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Amount Paid</th>
                        <th style="padding: 12px 14px; text-align: right; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($record->payments as $idx => $payment)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; font-size: 13px; color: #64748b; font-weight: 600;">{{ $idx + 1 }}</td>
                            <td style="padding: 12px 14px; font-size: 13px; font-family: monospace; font-weight: 700; color: #0f172a;">
                                {{ $payment->trn ?: ('PAY-' . str_pad($payment->id, 5, '0', STR_PAD_LEFT)) }}
                            </td>
                            <td style="padding: 12px 14px; font-size: 13px; color: #334155;">
                                {{ ($payment->payment_date ?? $payment->created_at)?->format('M d, Y h:i A') ?? '—' }}
                            </td>
                            <td style="padding: 12px 14px; font-size: 13px; font-weight: 600; color: #334155;">
                                {{ $payment->method ?? 'Office Payment' }}
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; background: #dcfce7; color: #166534;">
                                    {{ ucfirst($payment->status ?? 'Paid') }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: right; font-size: 14px; font-weight: 800; color: #059669;">
                                ₱{{ number_format((float) $payment->amount_paid, 2) }}
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <button type="button" onclick="openFinancingReceiptModal('{{ route('financing.receipt', $payment->id) }}')" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-color: #3b82f6; color: #3b82f6; display: inline-flex; align-items: center; cursor: pointer;">
                                    Receipt
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 32px 16px; text-align: center; color: #64748b; font-size: 14px;">
                                No payments have been recorded for this financing account yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('paymentRecordsModal').style.display='none';">Close</button>
        </div>
    </div>
</div>

<!-- In-Page Receipt Preview Modal -->
<div id="financingReceiptModal" class="bill-modal" style="display: none; align-items: center; justify-content: center; z-index: 3200; background: rgba(15,23,42,0.7); backdrop-filter: blur(8px); padding: 20px;">
    <div class="bill-modal-content" style="max-width: 960px; width: 95%; max-height: 94vh; overflow: hidden; padding: 20px 24px; border-radius: 20px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">Payment Receipt</h2>
                <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">View-only official receipt preview</p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="btn btn-outline" onclick="printFinancingReceiptFrame()" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #334155;">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H7v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
                <button type="button" class="btn btn-outline" onclick="closeFinancingReceiptModal()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer;">
                    ✕ Close
                </button>
            </div>
        </div>
        <div style="flex: 1; min-height: 0; width: 100%;">
            <iframe id="financingReceiptFrame" src="about:blank" style="width: 100%; height: 75vh; border: 1px solid #e2e8f0; border-radius: 10px; background: #525659;"></iframe>
        </div>
    </div>
</div>

<!-- Modal overlay for payment -->
<div id="paymentModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:16px; width:400px; max-width:90%; overflow:hidden;">
        <div style="background:#10b981; color:#fff; padding:16px 24px; font-weight:700; display:flex; justify-content:space-between;">
            <span>Record Payment</span>
            <span style="cursor:pointer;" onclick="document.getElementById('paymentModal').style.display='none';">&times;</span>
        </div>
        <form method="POST" action="{{ route('financing.pay', $record->id) }}">
            @csrf
            <div style="padding:24px;">
                <p style="font-size:14px; color:#475569; margin-top:0;">Enter the amount the buyer paid to clear their remaining shortfall balance.</p>
                <div class="form-group">
                    <label>Payment Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required max="{{ $record->balance }}" value="{{ $record->balance }}">
                </div>
                <div class="form-group" style="margin-top: 12px;">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}">
                </div>
            </div>
            <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; gap:12px; justify-content:flex-end;">
                <button type="button" class="btn-submit" style="background:#e2e8f0; color:#475569;" onclick="document.getElementById('paymentModal').style.display='none';">Cancel</button>
                <button type="submit" class="btn-submit" style="background:#10b981;">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>
<script>
    function toggleCashFields() {
        const method = document.getElementById('financingMethod');
        const isCash = method && method.value === 'Cash';
        const loanFields = document.getElementById('loanFields');
        if (loanFields) {
            loanFields.style.display = isCash ? 'none' : '';
        }
        document.querySelectorAll('.loan-only').forEach(function(el) {
            el.style.display = isCash ? 'none' : '';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const method = document.getElementById('financingMethod');
        if (method) {
            method.addEventListener('change', toggleCashFields);
        }
        toggleCashFields();

        const numberInputs = document.querySelectorAll('input[type="number"]');
        
        numberInputs.forEach(input => {
            input.type = 'text';
            
            if(input.value) {
                const val = parseFloat(input.value);
                if(!isNaN(val)) {
                    const parts = val.toString().split('.');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    input.value = parts.join('.');
                }
            }
            
            input.addEventListener('input', function(e) {
                let val = this.value.replace(/[^0-9.]/g, '');
                const parts = val.split('.');
                if (parts.length > 2) {
                    val = parts[0] + '.' + parts.slice(1).join('');
                }
                
                if (val) {
                    const numberParts = val.split('.');
                    let integerPart = numberParts[0];
                    const decimalPart = numberParts.length > 1 ? '.' + numberParts[1] : '';
                    integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    this.value = integerPart + decimalPart;
                } else {
                    this.value = '';
                }
            });
        });
        
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                numberInputs.forEach(input => {
                    input.value = input.value.replace(/,/g, '');
                });
            });
        });
    });
    function toggleRetentionHold() {
        const sel = document.getElementById('retentionOnHold');
        sel.value = sel.value === '1' ? '0' : '1';
        sel.form.requestSubmit();
    }

    function openFinancingReceiptModal(url) {
        const frame = document.getElementById('financingReceiptFrame');
        if (frame) frame.src = url;
        const modal = document.getElementById('financingReceiptModal');
        if (modal) modal.style.display = 'flex';
    }

    function closeFinancingReceiptModal() {
        const modal = document.getElementById('financingReceiptModal');
        if (modal) modal.style.display = 'none';
        const frame = document.getElementById('financingReceiptFrame');
        if (frame) frame.src = 'about:blank';
    }

    function printFinancingReceiptFrame() {
        const frame = document.getElementById('financingReceiptFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        }
    }
</script>
@endsection


