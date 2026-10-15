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

    .env-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 14px;
    }

    .env-card {
        border: 1.5px solid var(--brand-border);
        border-radius: var(--radius-sm);
        padding: 10px 14px;
        cursor: pointer;
        transition: all 0.15s ease;
        background: #fff;
    }

    .env-card.active {
        border-color: var(--brand-primary);
        background: rgba(0, 14, 143, 0.04);
    }

    .status-box {
        padding: 12px 16px;
        border-radius: var(--radius-sm);
        font-size: 13px;
        margin-top: 14px;
        display: none;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 18px; font-weight: 700; color: var(--brand-dark);">Gateway & API Settings</h2>
                <p style="font-size: 13px; color: var(--brand-muted); margin-top: 2px;">Configure PremierPayFast API credentials and customize QR UI</p>
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
                🔑 API Credentials
            </button>
            <button type="button" class="settings-nav-btn" onclick="switchTab('brand-tab', this)">
                🎨 UI & Branding
            </button>
        </div>

        <form action="{{ route('payfast.settings.save') }}" method="POST" id="settings-form">
            @csrf

            <!-- TAB 1: API CREDENTIALS -->
            <div id="api-tab">
                <div class="form-group">
                    <label class="form-label">API Environment</label>
                    <div class="env-selector">
                        <div class="env-card {{ ($settings['base_url'] ?? '') != 'https://ipg.premierpayfast.com/api' ? 'active' : '' }}" onclick="selectEnv('https://uat.premierpayfast.com/api', this)">
                            <div style="font-weight: 700; font-size: 13px; color: var(--brand-primary);">Sandbox / Testing (UAT)</div>
                            <div style="font-size: 11px; color: var(--brand-muted); margin-top: 2px;">Testing with test accounts</div>
                        </div>
                        <div class="env-card {{ ($settings['base_url'] ?? '') == 'https://ipg.premierpayfast.com/api' ? 'active' : '' }}" onclick="selectEnv('https://ipg.premierpayfast.com/api', this)">
                            <div style="font-weight: 700; font-size: 13px; color: #16a34a;">Live / Production</div>
                            <div style="font-size: 11px; color: var(--brand-muted); margin-top: 2px;">Real customer payments</div>
                        </div>
                    </div>
                    <input type="hidden" name="base_url" id="base_url_input" value="{{ old('base_url', $settings['base_url'] ?? 'https://uat.premierpayfast.com/api') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="merchant_id">
                        Merchant ID *
                    </label>
                    <input type="text" id="merchant_id" name="merchant_id" 
                           value="{{ old('merchant_id', $settings['merchant_id'] ?? '') }}" 
                           placeholder="e.g. 100234" class="form-control" autocomplete="off">
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
                    <span style="font-size: 11px; color: var(--brand-muted);">Leave blank if your merchant profile doesn't require hash signing</span>
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
        <span>Stored securely in .env & config</span>
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

    function selectEnv(url, card) {
        document.getElementById('base_url_input').value = url;
        document.querySelectorAll('.env-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
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
        const baseUrl = document.getElementById('base_url_input').value;

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
