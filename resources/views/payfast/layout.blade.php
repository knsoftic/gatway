<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PayFast QR Gateway')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: {{ config('payfast.brand_color', '#000e8f') }};
            --brand-accent: {{ config('payfast.accent_color', '#6bc197') }};
            --brand-dark: #0f172a;
            --brand-light: #f8fafc;
            --brand-surface: #ffffff;
            --brand-border: #e2e8f0;
            --brand-text: #1e293b;
            --brand-muted: #64748b;
            --brand-success: #10b981;
            --brand-danger: #ef4444;
            --brand-warning: #f59e0b;
            --radius-lg: 18px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-card: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
            --shadow-glow: 0 0 25px -5px rgba(0, 14, 143, 0.25);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 50%, #f8fafc 100%);
            min-height: 100vh;
            color: var(--brand-text);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .payment-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid var(--brand-border);
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--brand-primary);
            box-shadow: var(--shadow-subtle);
        }

        .brand-badge .dot {
            width: 8px;
            height: 8px;
            background: var(--brand-accent);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .card {
            background: var(--brand-surface);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            position: relative;
        }

        .card-header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--brand-border);
            background: linear-gradient(to bottom, #ffffff, #fafafa);
        }

        .card-body {
            padding: 28px;
        }

        .card-footer {
            padding: 16px 28px;
            background: #f8fafc;
            border-top: 1px solid var(--brand-border);
            text-align: center;
            font-size: 12px;
            color: var(--brand-muted);
        }

        .amount-display {
            text-align: center;
            margin-bottom: 20px;
        }

        .amount-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--brand-muted);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .amount-value {
            font-size: 34px;
            font-weight: 800;
            color: var(--brand-dark);
            letter-spacing: -0.02em;
        }

        .amount-value span {
            font-size: 20px;
            color: var(--brand-primary);
            margin-right: 2px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, var(--brand-primary) 0%, #1a237e 100%);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 14, 143, 0.25);
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 14, 143, 0.35);
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 12px 18px;
            background: #ffffff;
            color: var(--brand-text);
            border: 1px solid var(--brand-border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--brand-text);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid var(--brand-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: all 0.15s ease;
            outline: none;
            background: #fff;
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(0, 14, 143, 0.1);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
        }

        .powered-by {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: var(--brand-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .powered-by strong {
            color: var(--brand-primary);
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="payment-container">
        <div class="brand-header">
            <div class="brand-badge">
                <span class="dot"></span>
                <span>{{ config('payfast.brand_name', 'PayFast Gateway') }}</span>
            </div>
        </div>

        @yield('content')

        <div class="powered-by">
            <span>Powered by</span>
            <strong>PremierPayFast</strong>
            <span>• Direct QR Pay</span>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
