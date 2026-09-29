@extends('layouts.admin')
@section('title', 'Create Financing Record')

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
            <h1>Create Financing Record</h1>
            <p>Select a buyer to initiate their financing and loan tracking process.</p>
        </div>
        <div class="bill-actions">
            <a href="{{ route('financing.index') }}" class="pay-button" style="background: #e2e8f0; color: #475569; text-decoration: none;">Back</a>
        </div>
    </div>

    @if(session('error'))
        <div style="background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-weight: 600; font-size: 14px;">
            {{ session('error') }}
        </div>
    @endif

    <div class="analytic-card" style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; max-width: 600px;">
        <div style="padding: 16px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; color: #0f172a;">
            Select Buyer from Master List
        </div>
        <div style="padding: 24px;">
            <form action="{{ route('financing.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Select Buyer</label>
                    <select name="buyer_id" class="form-control" required>
                        <option value="">-- Choose Buyer --</option>
                        @foreach($buyers as $buyer)
                            <option value="{{ $buyer->id }}">
                                {{ $buyer->first_name }} {{ $buyer->last_name }} 
                                (Block {{ $buyer->block_no ?? 'N/A' }}, Lot {{ $buyer->lot_no ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                    <p style="font-size: 12px; color: #64748b; margin-top: 8px;">
                        Selecting a buyer will automatically sync their contract price, equity, and lot information from the Master List.
                    </p>
                </div>
                <div style="margin-top: 24px; text-align: right;">
                    <button type="submit" class="btn-submit">Generate Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

