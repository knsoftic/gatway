@extends('payfast.layout')

@section('title', 'Complete Payment - ' . config('payfast.brand_name'))

@push('styles')
<style>
    .wallet-select-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 16px;
    }

    .wallet-option {
        position: relative;
    }

    .wallet-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .wallet-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
        border: 2px solid var(--brand-border);
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
    }

    .wallet-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .wallet-option input[type="radio"]:checked + .wallet-card {
        border-color: var(--brand-primary);
        background: rgba(0, 14, 143, 0.04);
        box-shadow: 0 0 0 1px var(--brand-primary);
    }

    .wallet-badge {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        border: 2px solid var(--brand-border);
        display: inline-block;
        margin-left: auto;
    }

    .wallet-option input[type="radio"]:checked + .wallet-card .wallet-badge {
        border-color: var(--brand-primary);
        background: var(--brand-primary);
    }

    .otp-input-group {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin: 16px 0;
    }

    .otp-input {
        width: 100%;
        letter-spacing: 8px;
        text-align: center;
        font-size: 24px;
        font-weight: 700;
        padding: 12px;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span class="amount-label">Pay Amount</span>
                <div style="font-size: 22px; font-weight: 800; color: var(--brand-dark);">
                    PKR {{ number_format($transaction->amount, 2) }}
                </div>
            </div>
            <a href="{{ route('payfast.qr.show', $transaction->qr_token) }}" style="font-size: 12px; color: var(--brand-primary); text-decoration: none; font-weight: 600;">
                ← View QR
            </a>
        </div>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert-error">
                <ul style="padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('otp_message'))
            <div class="alert-success">
                {{ session('otp_message') }}
            </div>
        @endif

        @php
            $isOtpStep = session('step') === 'otp';
        @endphp

        @if (!$isOtpStep)
            {{-- STEP 1: Customer & Account Info --}}
            <form action="{{ route('payfast.validate', $transaction->qr_token) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">Select Payment Method *</label>
                    <div class="wallet-select-grid">
                        <label class="wallet-option">
                            <input type="radio" name="account_type_id" value="1" {{ old('account_type_id', '1') == '1' ? 'checked' : '' }} required>
                            <div class="wallet-card">
                                <span style="font-weight: 600; font-size: 13px;">Easypaisa</span>
                                <span class="wallet-badge"></span>
                            </div>
                        </label>

                        <label class="wallet-option">
                            <input type="radio" name="account_type_id" value="2" {{ old('account_type_id') == '2' ? 'checked' : '' }} required>
                            <div class="wallet-card">
                                <span style="font-weight: 600; font-size: 13px;">JazzCash</span>
                                <span class="wallet-badge"></span>
                            </div>
                        </label>

                        <label class="wallet-option">
                            <input type="radio" name="account_type_id" value="3" {{ old('account_type_id') == '3' ? 'checked' : '' }} required>
                            <div class="wallet-card">
                                <span style="font-weight: 600; font-size: 13px;">Bank Account</span>
                                <span class="wallet-badge"></span>
                            </div>
                        </label>

                        <label class="wallet-option">
                            <input type="radio" name="account_type_id" value="4" {{ old('account_type_id') == '4' ? 'checked' : '' }} required>
                            <div class="wallet-card">
                                <span style="font-weight: 600; font-size: 13px;">UnionPay / Card</span>
                                <span class="wallet-badge"></span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="customer_mobile">Mobile Number (Account Holder) *</label>
                    <input type="tel" id="customer_mobile" name="customer_mobile" 
                           value="{{ old('customer_mobile', $transaction->customer_mobile) }}" 
                           placeholder="03001234567" required class="form-control" maxlength="13">
                    <span style="font-size: 11px; color: var(--brand-muted);">Enter 11-digit mobile number linked to your account</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="customer_name">Full Name (Optional)</label>
                    <input type="text" id="customer_name" name="customer_name" 
                           value="{{ old('customer_name', $transaction->customer_name) }}" 
                           placeholder="Muhammad Ali" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label" for="customer_email">Email Address (Optional)</label>
                    <input type="email" id="customer_email" name="customer_email" 
                           value="{{ old('customer_email', $transaction->customer_email) }}" 
                           placeholder="ali@example.com" class="form-control">
                </div>

                <button type="submit" class="btn-primary">
                    <span>Continue to Verification</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </button>
            </form>
        @else
            {{-- STEP 2: OTP Verification --}}
            <form action="{{ route('payfast.process', $transaction->qr_token) }}" method="POST">
                @csrf

                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </div>
                    <h3 style="font-size: 16px; font-weight: 700;">Enter OTP Code</h3>
                    <p style="font-size: 13px; color: var(--brand-muted); margin-top: 4px;">
                        Please enter the OTP sent to <strong>{{ $transaction->customer_mobile }}</strong>
                    </p>
                </div>

                <div class="form-group">
                    <input type="text" id="otp" name="otp" required 
                           class="form-control otp-input" 
                           placeholder="••••••" maxlength="8" autofocus>
                </div>

                <button type="submit" class="btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    Confirm & Authorize Payment
                </button>

                <div style="text-align: center; margin-top: 14px;">
                    <a href="{{ route('payfast.pay', $transaction->qr_token) }}" style="font-size: 13px; color: var(--brand-muted); text-decoration: none;">
                        Cancel and change mobile number
                    </a>
                </div>
            </form>
        @endif
    </div>

    <div class="card-footer">
        🔒 Encrypted & Verified by PremierPayFast Gateway
    </div>
</div>
@endsection
