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

    public function __construct()
    {
        $this->baseUrl    = rtrim(self::getSetting('base_url', 'https://uat.premierpayfast.com/api'), '/');
        $this->merchantId = (string) self::getSetting('merchant_id', '');
        $this->securedKey = (string) self::getSetting('secured_key', '');
        $this->hashKey    = (string) self::getSetting('hash_key', '');
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
            'base_url'     => config('payfast.base_url', 'https://uat.premierpayfast.com/api'),
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
            'PAYFAST_BASE_URL'     => $updated['base_url'] ?? 'https://uat.premierpayfast.com/api',
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

        if (empty($mId) || empty($sKey)) {
            return [
                'success' => false,
                'message' => 'Merchant ID and Secured Key are required to test connection.',
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
                        'message' => 'Connected successfully! Access Token received from PremierPayFast.',
                        'data'    => $data,
                    ];
                }
            }

            $errorMsg = $response->json('message') ?? $response->json('error_description') ?? $response->body();
            return [
                'success' => false,
                'message' => 'API returned error (' . $response->status() . '): ' . ($errorMsg ?: 'Unauthorized / Invalid credentials'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Step 1: Get Access Token
     */
    public function getAccessToken(string $customerIp = null): ?array
    {
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
