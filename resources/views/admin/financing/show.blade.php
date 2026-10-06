@extends('layouts.admin')
@section('title', 'Manage Financing Record')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
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
                            <div class="form-group"><label>Contract Price</label><input type="number" step="0.01" name="contract_price" class="form-control" value="{{ $record->contract_price }}"></div>
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
                        
                        <div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px;">&nbsp;</div>
                            <div class="form-group"><label>Gross Loan Released</label><input type="number" step="0.01" name="loan_release" class="form-control" style="border-color: #3b82f6;" value="{{ $record->loan_release }}"></div>
                            <div class="form-group"><label>Inspection/Processing Fee</label><input type="number" step="0.01" name="inspection_fee" class="form-control" style="color: #dc2626;" value="{{ $record->inspection_fee }}"></div>
                            <div class="form-group"><label>Retention/Conversion</label><input type="number" step="0.01" name="retention_fee" class="form-control" style="color: #dc2626;" value="{{ $record->retention_fee }}"></div>
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
                    <div class="list-item" style="margin-top: 12px;">
                        <span style="color:#64748b;">Gross Loan Release</span>
                        <span style="font-weight:700; color:#3b82f6;">₱{{ number_format($record->loan_release, 2) }}</span>
                    </div>
                    <div class="list-item">
                        <span style="color:#64748b;">Total Loan Deductions</span>
                        <span style="font-weight:700; color:#dc2626;">- ₱{{ number_format($record->total_amt_due, 2) }}</span>
                    </div>
                    <div class="list-item" style="background: #dcfce7; margin: 0 -24px -24px -24px; padding: 16px 24px; border-radius: 0 0 16px 16px;">
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
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Total Owed to Developer</div>
                    <div style="font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 16px;">₱{{ number_format($record->receivables, 2) }}</div>
                    
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Remaining Balance</div>
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 24px; color: {{ $record->balance > 0 ? '#dc2626' : '#059669' }}">₱{{ number_format($record->balance, 2) }}</div>
                    
                    @if($record->balance > 0)
                        <button onclick="document.getElementById('paymentModal').style.display='flex';" class="btn-submit" style="width: 100%; background: #10b981;">Record Buyer Payment</button>
                    @else
                        <div style="background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; font-weight: 600; font-size: 14px;">Balance Fully Paid</div>
                    @endif
                </div>
            </div>
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
            </div>
            <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; gap:12px; justify-content:flex-end;">
                <button type="button" class="btn-submit" style="background:#e2e8f0; color:#475569;" onclick="document.getElementById('paymentModal').style.display='none';">Cancel</button>
                <button type="submit" class="btn-submit" style="background:#10b981;">Confirm Payment</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
</script>
@endsection


