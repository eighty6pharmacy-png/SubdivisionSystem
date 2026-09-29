@extends('layouts.buyer')
@section('title', 'Financial Dashboard')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-finance.css') }}">
<style>
    .status-badge {
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        display: inline-block;
    }
    .status-good { background: #dcfce7; color: #059669; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-fully-paid { background: #dbeafe; color: #2563eb; }
    
    .timeline {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 32px 0;
        position: relative;
    }
    .timeline::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 2px;
        background: #e2e8f0;
        z-index: 1;
    }
    .timeline-step {
        position: relative;
        z-index: 2;
        background: #fff;
        padding: 0 16px;
        text-align: center;
    }
    .timeline-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin: 0 auto 8px;
    }
    .timeline-step.active .timeline-circle {
        background: #3b82f6;
        color: #fff;
    }
    .timeline-step.completed .timeline-circle {
        background: #10b981;
        color: #fff;
    }
    .timeline-label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
    }
</style>

<div class="bill-container fade-in">
    <!-- Property Info Header -->
    <div class="bill-header">
        <div class="bill-title">
            <h1>Welcome, {{ $buyer->first_name }}</h1>
            <p>Block {{ $buyer->block_no }}, Lot {{ $buyer->lot_no }} • {{ $buyer->financing_method ?? 'Financing' }}</p>
        </div>
    </div>

    <!-- Progress Tracker -->
    <div class="analytic-card" style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px;">
        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Acquisition Progress</h3>
        @php
            $hasDp = $downpayments->count() > 0;
            $dpCompleted = $hasDp && $downpayments->where('status', 'Fully Paid')->count() == $downpayments->count();
            $finStatus = $financing ? $financing->status : null;
            
            $step1 = true; // Reservation is done
            $step2 = $hasDp;
            $step3 = $dpCompleted;
            $step4 = $finStatus == 'Cleared';
        @endphp
        <div class="timeline">
            <div class="timeline-step {{ $step1 ? 'completed' : '' }}">
                <div class="timeline-circle">1</div>
                <div class="timeline-label">Reserved</div>
            </div>
            <div class="timeline-step {{ $step3 ? 'completed' : ($step2 ? 'active' : '') }}">
                <div class="timeline-circle">2</div>
                <div class="timeline-label">Downpayment</div>
            </div>
            <div class="timeline-step {{ $step4 ? 'completed' : ($finStatus ? 'active' : '') }}">
                <div class="timeline-circle">3</div>
                <div class="timeline-label">Loan Processing</div>
            </div>
            <div class="timeline-step {{ $step4 ? 'active' : '' }}">
                <div class="timeline-circle">4</div>
                <div class="timeline-label">Turnover</div>
            </div>
        </div>
    </div>

</div>
@endsection
