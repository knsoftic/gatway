@extends('payfast.layout')

@section('title', 'Generate QR Payment - ' . config('payfast.brand_name'))

@section('content')
<div class="card">
    <div class="card-header">
        <h2 style="font-size: 18px; font-weight: 700; color: var(--brand-dark);">Generate QR Payment</h2>
        <p style="font-size: 13px; color: var(--brand-muted); margin-top: 4px;">Enter amount and details to generate a scannable QR code</p>
    </div>

    <div class="card-body">
        @if (empty($settings['merchant_id']) || empty($settings['secured_key']))
            <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: var(--radius-sm); padding: 12px 14px; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                <div style="font-size: 13px; color: #92400e;">
                    <strong>API Keys Needed:</strong> Enter your PremierPayFast Merchant ID & Key.
                </div>
                <a href="{{ route('payfast.settings') }}" style="font-size: 12px; font-weight: 700; color: #b45309; text-decoration: underline; white-space: nowrap;">
                    Add Keys →
                </a>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                <ul style="padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('payfast.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label" for="amount">Payment Amount (PKR) *</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 14px; top: 12px; font-weight: 700; color: var(--brand-muted);">Rs.</span>
                    <input type="number" step="0.01" min="1" id="amount" name="amount" value="{{ old('amount', '500') }}" required class="form-control" style="padding-left: 48px; font-size: 16px; font-weight: 600;" placeholder="0.00">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Order / Payment Description *</label>
                <input type="text" id="description" name="description" value="{{ old('description', 'Order Payment') }}" required class="form-control" placeholder="e.g. Order #1234 or Premium Subscription">
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Generate PayFast QR Code
            </button>
        </form>
    </div>

    <div class="card-footer">
        PremierPayFast Direct API integration • Instant QR generation
    </div>
</div>
@endsection
