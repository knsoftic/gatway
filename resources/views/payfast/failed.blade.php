@extends('payfast.layout')

@section('title', 'Payment Failed - ' . config('payfast.brand_name'))

@section('content')
<div class="card">
    <div class="card-body" style="text-align: center; padding-top: 36px;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </div>

        <h2 style="font-size: 20px; font-weight: 800; color: var(--brand-dark);">Payment Could Not Complete</h2>
        <p style="font-size: 13px; color: var(--brand-muted); margin-top: 4px;">
            {{ $transaction->status_msg ?: 'The transaction was declined or canceled by the provider.' }}
        </p>

        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 16px; margin: 24px 0; font-size: 13px; color: #991b1b; text-align: left;">
            <div><strong>Order ID:</strong> {{ $transaction->basket_id }}</div>
            <div style="margin-top: 4px;"><strong>Amount:</strong> PKR {{ number_format($transaction->amount, 2) }}</div>
            @if ($transaction->status_code)
                <div style="margin-top: 4px;"><strong>Error Code:</strong> {{ $transaction->status_code }}</div>
            @endif
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('payfast.pay', $transaction->qr_token) }}" class="btn-primary">
                Try Again
            </a>
            <a href="{{ route('payfast.qr.show', $transaction->qr_token) }}" class="btn-secondary">
                View QR
            </a>
        </div>
    </div>
</div>
@endsection
