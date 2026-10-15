<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayfastService
{
    protected string $baseUrl;
    protected string $merchantId;
    protected string $securedKey;
    protected string $hashKey;
    protected bool $demoMode;

    public function __construct()
    {
        $this->baseUrl    = rtrim(self::getSetting('base_url', 'https://uat.premierpayfast.com'), '/');
        $this->merchantId = (string) self::getSetting('merchant_id', '');
        $this->securedKey = (string) self::getSetting('secured_key', '');
        $this->hashKey    = (string) self::getSetting('hash_key', '');
        $this->demoMode   = (bool) self::getSetting('demo_mode', false);
    }

    /**
     * Get a setting by key with fallback to config()
     */
    public static function getSetting(string $key, $default = null)
    {
        $settings = self::getAllSettings();
        if (isset($settings[$key]) && $settings[$key] !== '') {
            return $settings[$key];
        }
        return config("payfast.{$key}", $default);
    }

    /**
     * Retrieve all saved settings from storage
     */
    public static function getAllSettings(): array
    {
        $path = storage_path('app/payfast_settings.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'merchant_id'  => config('payfast.merchant_id', ''),
            'secured_key'  => config('payfast.secured_key', ''),
            'hash_key'     => config('payfast.hash_key', ''),
            'base_url'     => config('payfast.base_url', 'https://uat.premierpayfast.com'),
            'demo_mode'    => config('payfast.demo_mode', false),
            'brand_name'   => config('payfast.brand_name', 'PayFast QR Gateway'),
            'brand_color'  => config('payfast.brand_color', '#000e8f'),
            'accent_color' => config('payfast.accent_color', '#6bc197'),
            'logo_url'     => config('payfast.logo_url', ''),
        ];
    }

    /**
     * Save settings to storage and update .env
     */
    public static function saveSettings(array $data): void
    {
        $current = self::getAllSettings();
        $updated = array_merge($current, array_filter($data, fn($v) => !is_null($v)));

        // Ensure boolean for demo_mode
        if (isset($data['demo_mode'])) {
            $updated['demo_mode'] = filter_var($data['demo_mode'], FILTER_VALIDATE_BOOLEAN);
        }

        // Save JSON in storage
        $path = storage_path('app/payfast_settings.json');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, json_encode($updated, JSON_PRETTY_PRINT));

        // Update .env file
        self::updateEnvFile([
            'PAYFAST_MERCHANT_ID'  => $updated['merchant_id'] ?? '',
            'PAYFAST_SECURED_KEY'  => $updated['secured_key'] ?? '',
            'PAYFAST_HASH_KEY'     => $updated['hash_key'] ?? '',
            'PAYFAST_BASE_URL'     => $updated['base_url'] ?? 'https://uat.premierpayfast.com',
            'PAYFAST_DEMO_MODE'    => ($updated['demo_mode'] ?? false) ? 'true' : 'false',
            'PAYFAST_BRAND_NAME'   => '"' . ($updated['brand_name'] ?? 'PayFast QR Gateway') . '"',
            'PAYFAST_BRAND_COLOR'  => '"' . ($updated['brand_color'] ?? '#000e8f') . '"',
            'PAYFAST_ACCENT_COLOR' => '"' . ($updated['accent_color'] ?? '#6bc197') . '"',
            'PAYFAST_LOGO_URL'     => '"' . ($updated['logo_url'] ?? '') . '"',
        ]);
    }

    /**
     * Safely update .env file
     */
    protected static function updateEnvFile(array $values): void
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * Test connection to PremierPayFast API
     */
    public function testConnection(?string $merchantId = null, ?string $securedKey = null, ?string $baseUrl = null): array
    {
        $mId = $merchantId ?: $this->merchantId;
        $sKey = $securedKey ?: $this->securedKey;
        $url = rtrim($baseUrl ?: $this->baseUrl, '/');

        if ($this->demoMode) {
            return [
                'success' => true,
                'message' => 'Demo Simulator Mode is ON. Simulated connection is successful! (All transactions will be approved for testing).',
            ];
        }

        if (empty($mId) || empty($sKey)) {
            return [
                'success' => false,
                'message' => 'Merchant ID and Secured Key are required to test connection.',
            ];
        }

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid Gateway Base URL.',
            ];
        }

        try {
            $response = Http::timeout(10)->asForm()->post("{$url}/token", [
                'merchant_id'  => $mId,
                'secured_key'  => $sKey,
                'grant_type'   => 'client_credentials',
                'customer_ip'  => request()->ip() ?: '127.0.0.1',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['token'])) {
                    return [
                        'success' => true,
                        'message' => 'Connected successfully! Valid Access Token received from PremierPayFast.',
                        'data'    => $data,
                    ];
                }
            }

            $status = $response->status();
            $body = $response->json('message') ?? $response->json('error_description') ?? $response->body();

            return [
                'success' => false,
                'message' => "Gateway reached (HTTP {$status}), but returned: " . (strip_tags(substr($body, 0, 150)) ?: 'Invalid credentials or endpoint'),
            ];
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Could not resolve host')) {
                return [
                    'success' => false,
                    'message' => "Host could not be resolved. The domain '{$url}' does not exist in DNS. Please check the exact Gateway Base URL provided in your PayFast merchant activation email.",
                ];
            }
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $msg,
            ];
        }
    }

    /**
     * Step 1: Get Access Token
     */
    public function getAccessToken(string $customerIp = null): ?array
    {
        if ($this->demoMode) {
            return [
                'token'      => 'demo_token_' . bin2hex(random_bytes(16)),
                'expires_in' => 3600,
            ];
        }

        $ip = $customerIp ?? request()->ip() ?? '127.0.0.1';

        try {
            $response = Http::timeout(15)->asForm()->post("{$this->baseUrl}/token", [
                'merchant_id'  => $this->merchantId,
                'secured_key'  => $this->securedKey,
                'grant_type'   => 'client_credentials',
                'customer_ip'  => $ip,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayFast Token Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('PayFast Token Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Step 2: Customer Validate — sends OTP to customer
     */
    public function customerValidate(string $token, array $data): ?array
    {
        if ($this->demoMode) {
            return [
                'code'           => '00',
                'message'        => 'Demo OTP sent! Enter 1234 or any 4-6 digit code to complete payment.',
                'transaction_id' => 'DEMO-TXN-' . time(),
                'otp_required'   => true,
            ];
        }

        $payload = [
            'basket_id'          => $data['basket_id'],
            'txnamt'             => $data['txnamt'],
            'order_date'         => $data['order_date'] ?? now()->format('Y-m-d'),
            'customer_mobile_no' => $data['customer_mobile_no'],
            'account_type_id'    => $data['account_type_id'],
        ];

        if (!empty($data['customer_email_address'])) {
            $payload['customer_email_address'] = $data['customer_email_address'];
        }

        if ($this->hashKey) {
            $hashString = $payload['basket_id'] . '+' . $payload['txnamt'];
            $payload['secured_hash'] = $this->generateHash($hashString);
        }

        try {
            $response = Http::timeout(20)->asForm()
                ->withToken($token)
                ->post("{$this->baseUrl}/customer/validate", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayFast Validate Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('PayFast Validate Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Step 3: Initiate Direct Transaction — charge with OTP
     */
    public function initiateTransaction(string $token, array $data): ?array
    {
        if ($this->demoMode) {
            return [
                'code'           => '00',
                'status_code'    => '00',
                'status_msg'     => 'Transaction Approved (Demo Mode)',
                'transaction_id' => $data['transaction_id'] ?? ('DEMO-TXN-' . time()),
                'basket_id'      => $data['basket_id'],
            ];
        }

        $payload = [
            'basket_id'          => $data['basket_id'],
            'txnamt'             => $data['txnamt'],
            'order_date'         => $data['order_date'] ?? now()->format('Y-m-d'),
            'customer_mobile_no' => $data['customer_mobile_no'],
            'account_type_id'    => $data['account_type_id'],
            'otp'                => $data['otp'],
            'transaction_id'     => $data['transaction_id'],
        ];

        if (!empty($data['eci'])) {
            $payload['eci'] = $data['eci'];
        }

        if ($this->hashKey) {
            $hashString = $payload['basket_id'] . '+' . $payload['txnamt'] . '+' . $payload['otp'];
            $payload['secured_hash'] = $this->generateHash($hashString);
        }

        try {
            $response = Http::timeout(25)->asForm()
                ->withToken($token)
                ->post("{$this->baseUrl}/transaction", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayFast Transaction Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('PayFast Transaction Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check Transaction Status
     */
    public function getTransactionStatus(string $token, string $transactionId): ?array
    {
        if ($this->demoMode) {
            return [
                'status'     => 'completed',
                'status_msg' => 'Approved',
            ];
        }

        try {
            $response = Http::timeout(15)->withToken($token)
                ->get("{$this->baseUrl}/transaction/{$transactionId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('PayFast Status Check Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('PayFast Status Check Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate HMAC-SHA256 hash
     */
    protected function generateHash(string $data): string
    {
        return hash_hmac('sha256', $data, $this->hashKey);
    }
}
