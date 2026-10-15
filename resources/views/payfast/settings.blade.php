@extends('payfast.layout')

@section('title', 'API Keys & Gateway Settings - ' . \App\Services\PayfastService::getSetting('brand_name', 'PayFast Gateway'))

@push('styles')
<style>
    .settings-nav {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: var(--radius-sm);
    }

    .settings-nav-btn {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        color: var(--brand-muted);
        background: none;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .settings-nav-btn.active {
        background: #ffffff;
        color: var(--brand-dark);
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }

    .demo-mode-card {
        background: #f0fdf4;
        border: 1.5px solid #86efac;
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
    }

    .demo-mode-card.inactive {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    /* Switch styling */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 26px;
        flex-shrink: 0;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 26px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #10b981;
    }

    input:checked + .slider:before {
        transform: translateX(22px);
    }

    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .color-picker-input {
        width: 44px;
        height: 44px;
        padding: 0;
        border: 2px solid var(--brand-border);
        border-radius: var(--radius-sm);
        cursor: pointer;
        background: none;
    }

    .password-toggle-wrapper {
        position: relative;
    }

    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--brand-muted);
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }

    .url-presets {
        display: flex;
        gap: 8px;
        margin-top: 6px;
        flex-wrap: wrap;
    }

    .preset-pill {
        font-size: 11px;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        background: #f1f5f9;
        color: var(--brand-primary);
        border: 1px solid var(--brand-border);
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .preset-pill:hover {
        background: #e2e8f0;
    }

    .status-box {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        margin-top: 14px;
        display: none;
        line-height: 1.5;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 18px; font-weight: 700; color: var(--brand-dark);">Gateway & API Settings</h2>
                <p style="font-size: 13px; color: var(--brand-muted); margin-top: 2px;">Configure PremierPayFast API credentials or enable Demo Simulator Mode</p>
            </div>
            <a href="{{ route('payfast.create') }}" style="font-size: 12px; font-weight: 600; color: var(--brand-primary); text-decoration: none;">
                ← Back
            </a>
        </div>
    </div>

    <div class="card-body">
        @if (session('success'))
            <div class="alert-success">
                ✓ {{ session('success') }}
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

        <div class="settings-nav">
            <button type="button" class="settings-nav-btn active" onclick="switchTab('api-tab', this)">
                🔑 API Credentials & Simulator
            </button>
            <button type="button" class="settings-nav-btn" onclick="switchTab('brand-tab', this)">
                🎨 UI & Branding
            </button>
        </div>

        <form action="{{ route('payfast.settings.save') }}" method="POST" id="settings-form">
            @csrf

            <!-- TAB 1: API CREDENTIALS & SIMULATOR -->
            <div id="api-tab">

                <!-- DEMO MODE TOGGLE -->
                @php
                    $isDemo = (bool) ($settings['demo_mode'] ?? false);
                @endphp
                <div class="demo-mode-card {{ $isDemo ? '' : 'inactive' }}" id="demo-card">
                    <div>
                        <div style="font-weight: 700; font-size: 14px; color: {{ $isDemo ? '#166534' : 'var(--brand-dark)' }}; display: flex; align-items: center; gap: 6px;">
                            <span>🧪 Demo Simulator Mode</span>
                            <span style="font-size: 10px; background: {{ $isDemo ? '#22c55e' : '#94a3b8' }}; color: #fff; padding: 2px 6px; border-radius: 9999px;" id="demo-badge">
                                {{ $isDemo ? 'ACTIVE' : 'OFF' }}
                            </span>
                        </div>
                        <p style="font-size: 12px; color: var(--brand-muted); margin-top: 4px;">
                            Turn ON to test the entire QR flow (generating, scanning, OTP, receipts) without needing real API approval from PremierPayFast.
                        </p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="demo_mode" value="1" id="demo_mode_checkbox" {{ $isDemo ? 'checked' : '' }} onchange="toggleDemoCard(this.checked)">
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label" for="base_url">
                        Gateway Base URL *
                    </label>
                    <input type="url" id="base_url" name="base_url" 
                           value="{{ old('base_url', $settings['base_url'] ?? 'https://uat.premierpayfast.com') }}" 
                           required class="form-control" style="font-family: monospace; font-size: 13px;"
                           placeholder="https://uat.premierpayfast.com">
                    
                    <div class="url-presets">
                        <span style="font-size: 11px; color: var(--brand-muted); align-self: center;">Quick Presets:</span>
                        <button type="button" class="preset-pill" onclick="setBaseUrl('https://uat.premierpayfast.com')">
                            Official Sandbox (uat.premierpayfast.com)
                        </button>
                        <button type="button" class="preset-pill" onclick="setBaseUrl('https://premierpayfast.com')">
                            Production (premierpayfast.com)
                        </button>
                    </div>
                    <span style="font-size: 11px; color: var(--brand-muted); display: block; margin-top: 6px;">
                        💡 <strong>Note:</strong> Enter the exact Gateway URL provided in your PayFast merchant welcome email. (Avoid invalid subdomains like ipg.* that do not exist).
                    </span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="merchant_id">
                        Merchant ID *
                    </label>
                    <input type="text" id="merchant_id" name="merchant_id" 
                           value="{{ old('merchant_id', $settings['merchant_id'] ?? '') }}" 
                           placeholder="e.g. 100234 (Or test ID if in demo mode)" class="form-control" autocomplete="off">
                    <span style="font-size: 11px; color: var(--brand-muted);">Provided by PremierPayFast onboarding team</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="secured_key">
                        Secured Key *
                    </label>
                    <div class="password-toggle-wrapper">
                        <input type="password" id="secured_key" name="secured_key" 
                               value="{{ old('secured_key', $settings['secured_key'] ?? '') }}" 
                               placeholder="e.g. 7f8a9b2c3d4e..." class="form-control" autocomplete="off">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('secured_key', this)">Show</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="hash_key">
                        Hash Key (HMAC-SHA256 Signing Key)
                    </label>
                    <div class="password-toggle-wrapper">
                        <input type="password" id="hash_key" name="hash_key" 
                               value="{{ old('hash_key', $settings['hash_key'] ?? '') }}" 
                               placeholder="Optional HMAC key for payload signing" class="form-control" autocomplete="off">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('hash_key', this)">Show</button>
                    </div>
                    <span style="font-size: 11px; color: var(--brand-muted);">Leave blank if your merchant profile does not require HMAC signature</span>
                </div>

                <div style="margin-top: 10px; margin-bottom: 20px;">
                    <button type="button" class="btn-secondary" id="test-connection-btn" onclick="testApiConnection()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        Test API Connection
                    </button>
                    <div id="test-status-box" class="status-box"></div>
                </div>
            </div>

            <!-- TAB 2: BRANDING & CUSTOMIZATION -->
            <div id="brand-tab" style="display: none;">
                <div class="form-group">
                    <label class="form-label" for="brand_name">Brand / Store Name</label>
                    <input type="text" id="brand_name" name="brand_name" 
                           value="{{ old('brand_name', $settings['brand_name'] ?? 'PayFast QR Gateway') }}" 
                           class="form-control" placeholder="My Store / Business Name">
                </div>

                <div class="form-group">
                    <label class="form-label">Primary Brand Color (Buttons & Headers)</label>
                    <div class="color-picker-wrapper">
                        <input type="color" id="brand_color_picker" 
                               value="{{ old('brand_color', $settings['brand_color'] ?? '#000e8f') }}" 
                               class="color-picker-input" onchange="syncColor('brand_color', this.value)">
                        <input type="text" id="brand_color" name="brand_color" 
                               value="{{ old('brand_color', $settings['brand_color'] ?? '#000e8f') }}" 
                               class="form-control" style="font-family: monospace; font-size: 14px;" 
                               oninput="syncPicker('brand_color_picker', this.value)">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Accent Color (Badges & Highlights)</label>
                    <div class="color-picker-wrapper">
                        <input type="color" id="accent_color_picker" 
                               value="{{ old('accent_color', $settings['accent_color'] ?? '#6bc197') }}" 
                               class="color-picker-input" onchange="syncColor('accent_color', this.value)">
                        <input type="text" id="accent_color" name="accent_color" 
                               value="{{ old('accent_color', $settings['accent_color'] ?? '#6bc197') }}" 
                               class="form-control" style="font-family: monospace; font-size: 14px;" 
                               oninput="syncPicker('accent_color_picker', this.value)">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="logo_url">Store Logo URL (Optional)</label>
                    <input type="url" id="logo_url" name="logo_url" 
                           value="{{ old('logo_url', $settings['logo_url'] ?? '') }}" 
                           class="form-control" placeholder="https://yourdomain.com/logo.png">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Settings & API Keys
            </button>
        </form>
    </div>

    <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
        <span>Saved in .env & config</span>
        <a href="{{ route('payfast.create') }}" style="color: var(--brand-muted); text-decoration: none; font-size: 11px;">
            Go to Generator →
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function switchTab(tabId, btn) {
        document.getElementById('api-tab').style.display = tabId === 'api-tab' ? 'block' : 'none';
        document.getElementById('brand-tab').style.display = tabId === 'brand-tab' ? 'block' : 'none';
        
        document.querySelectorAll('.settings-nav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function setBaseUrl(url) {
        document.getElementById('base_url').value = url;
    }

    function toggleDemoCard(checked) {
        const card = document.getElementById('demo-card');
        const badge = document.getElementById('demo-badge');
        if (checked) {
            card.classList.remove('inactive');
            badge.style.background = '#22c55e';
            badge.innerText = 'ACTIVE';
        } else {
            card.classList.add('inactive');
            badge.style.background = '#94a3b8';
            badge.innerText = 'OFF';
        }
    }

    function togglePasswordVisibility(fieldId, btn) {
        const input = document.getElementById(fieldId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerText = 'Hide';
        } else {
            input.type = 'password';
            btn.innerText = 'Show';
        }
    }

    function syncColor(textId, value) {
        document.getElementById(textId).value = value;
    }

    function syncPicker(pickerId, value) {
        if (/^#[0-9A-F]{6}$/i.test(value)) {
            document.getElementById(pickerId).value = value;
        }
    }

    function testApiConnection() {
        const btn = document.getElementById('test-connection-btn');
        const statusBox = document.getElementById('test-status-box');
        const merchantId = document.getElementById('merchant_id').value;
        const securedKey = document.getElementById('secured_key').value;
        const baseUrl = document.getElementById('base_url').value;

        btn.disabled = true;
        btn.innerText = 'Testing connection...';
        statusBox.style.display = 'none';

        fetch("{{ route('payfast.settings.test') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                merchant_id: merchantId,
                secured_key: securedKey,
                base_url: baseUrl
            })
        })
        .then(res => res.json())
        .then(data => {
            statusBox.style.display = 'block';
            if (data.success) {
                statusBox.style.background = '#f0fdf4';
                statusBox.style.border = '1px solid #bbf7d0';
                statusBox.style.color = '#166534';
                statusBox.innerHTML = `<strong>✓ Success:</strong> ${data.message}`;
            } else {
                statusBox.style.background = '#fef2f2';
                statusBox.style.border = '1px solid #fecaca';
                statusBox.style.color = '#991b1b';
                statusBox.innerHTML = `<strong>✕ Error:</strong> ${data.message}`;
            }
        })
        .catch(err => {
            statusBox.style.display = 'block';
            statusBox.style.background = '#fef2f2';
            statusBox.style.border = '1px solid #fecaca';
            statusBox.style.color = '#991b1b';
            statusBox.innerHTML = '<strong>✕ Network Error:</strong> Could not reach local test endpoint.';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Test API Connection`;
        });
    }
</script>
@endpush
