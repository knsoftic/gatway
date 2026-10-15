@extends('payfast.layout')

@section('title', 'Scan & Pay - ' . config('payfast.brand_name'))

@push('styles')
<style>
    .qr-box {
        background: #ffffff;
        border-radius: var(--radius-md);
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px dashed #cbd5e1;
        margin: 20px 0;
        position: relative;
    }

    .qr-frame {
        width: 220px;
        height: 220px;
        background: #fff;
        padding: 12px;
        border-radius: var(--radius-md);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .qr-frame img, .qr-frame canvas {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #fef3c7;
        color: #92400e;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 12px;
    }

    .status-badge.polling {
        background: #e0f2fe;
        color: #0369a1;
    }

    .status-badge .spinner {
        width: 10px;
        height: 10px;
        border: 2px solid currentColor;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .supported-wallets {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 14px;
        flex-wrap: wrap;
    }

    .wallet-pill {
        font-size: 11px;
        padding: 4px 8px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 6px;
        font-weight: 600;
    }

    .copy-link-btn {
        background: none;
        border: none;
        color: var(--brand-primary);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 10px;
        border-radius: 6px;
        transition: background 0.15s ease;
    }

    .copy-link-btn:hover {
        background: #f1f5f9;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header" style="text-align: center;">
        <div class="amount-label">Amount to Pay</div>
        <div class="amount-value">
            <span>PKR</span>{{ number_format($transaction->amount, 2) }}
        </div>
        <div style="font-size: 13px; color: var(--brand-muted); margin-top: 4px;">
            {{ $transaction->description }}
        </div>
        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">
            Order ID: <code>{{ $transaction->basket_id }}</code>
        </div>
    </div>

    <div class="card-body" style="text-align: center;">
        <p style="font-size: 14px; color: var(--brand-text); font-weight: 600;">
            Scan with any Mobile Banking or Wallet App
        </p>

        <div class="qr-box">
            <div class="qr-frame" id="qrcode-container">
                <!-- Fallback / Generated QR Code -->
                <img id="qr-img" 
                     src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={{ urlencode($paymentUrl) }}&color=000e8f&bgcolor=ffffff" 
                     alt="Scan to Pay QR Code">
            </div>

            <div class="status-badge polling" id="status-badge">
                <span class="spinner"></span>
                <span id="status-text">Waiting for payment...</span>
            </div>

            <div class="supported-wallets">
                <span class="wallet-pill">Easypaisa</span>
                <span class="wallet-pill">JazzCash</span>
                <span class="wallet-pill">Raast QR</span>
                <span class="wallet-pill">All Banks</span>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 16px;">
            <a href="{{ $paymentUrl }}" class="btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                    <line x1="2" y1="10" x2="22" y2="10"></line>
                </svg>
                Pay On This Device
            </a>

            <button type="button" class="copy-link-btn" onclick="copyPaymentUrl()" style="justify-content: center;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <span id="copy-btn-text">Copy Payment Link</span>
            </button>
        </div>
    </div>

    <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 11px; color: var(--brand-muted);">Auto-verifying transaction</span>
        <span style="font-size: 11px; color: var(--brand-accent); font-weight: 600;">SSL Encrypted 256-bit</span>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Copy payment link helper
    function copyPaymentUrl() {
        const url = "{{ $paymentUrl }}";
        navigator.clipboard.writeText(url).then(() => {
            const label = document.getElementById('copy-btn-text');
            const original = label.innerText;
            label.innerText = 'Link Copied!';
            setTimeout(() => { label.innerText = original; }, 2000);
        });
    }

    // Real-time status polling
    const checkStatusUrl = "{{ route('payfast.status', $transaction->qr_token) }}";
    const successUrl = "{{ route('payfast.success', $transaction->qr_token) }}";
    const failedUrl = "{{ route('payfast.failed', $transaction->qr_token) }}";

    const pollInterval = setInterval(() => {
        fetch(checkStatusUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'completed') {
                clearInterval(pollInterval);
                document.getElementById('status-text').innerText = 'Payment Received! Redirecting...';
                setTimeout(() => {
                    window.location.href = successUrl;
                }, 1000);
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                window.location.href = failedUrl;
            }
        })
        .catch(err => console.log('Polling check...', err));
    }, 3000);
</script>
@endpush
