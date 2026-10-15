@extends('payfast.layout')

@section('title', 'Payment Successful - ' . config('payfast.brand_name'))

@push('styles')
<style>
    .receipt-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px dashed var(--brand-border);
        font-size: 13px;
    }

    .receipt-row:last-child {
        border-bottom: none;
    }

    .receipt-label {
        color: var(--brand-muted);
    }

    .receipt-val {
        font-weight: 600;
        color: var(--brand-dark);
        text-align: right;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-body" style="text-align: center; padding-top: 36px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h2 style="font-size: 20px; font-weight: 800; color: var(--brand-dark);">Payment Successful!</h2>
        <p style="font-size: 13px; color: var(--brand-muted); margin-top: 4px;">Your transaction has been processed securely.</p>

        <div style="background: #f8fafc; border: 1px solid var(--brand-border); border-radius: var(--radius-md); padding: 18px; margin: 24px 0; text-align: left;">
            <div class="receipt-row">
                <span class="receipt-label">Amount Paid</span>
                <span class="receipt-val" style="color: var(--brand-primary); font-size: 15px; font-weight: 800;">
                    PKR {{ number_format($transaction->amount, 2) }}
                </span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Order ID</span>
                <span class="receipt-val">{{ $transaction->basket_id }}</span>
            </div>
            @if ($transaction->transaction_id)
            <div class="receipt-row">
                <span class="receipt-label">Transaction ID</span>
                <span class="receipt-val">{{ $transaction->transaction_id }}</span>
            </div>
            @endif
            <div class="receipt-row">
                <span class="receipt-label">Status</span>
                <span class="receipt-val" style="color: #16a34a;">PAID</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Date & Time</span>
                <span class="receipt-val">{{ $transaction->paid_at ? $transaction->paid_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</span>
            </div>
            @if ($transaction->customer_mobile)
            <div class="receipt-row">
                <span class="receipt-label">Paid Via</span>
                <span class="receipt-val">{{ $transaction->customer_mobile }}</span>
            </div>
            @endif
        </div>

        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Print Receipt
            </button>
            <a href="{{ route('payfast.create') }}" class="btn-primary">
                New Payment
            </a>
        </div>
    </div>
</div>
@endsection
