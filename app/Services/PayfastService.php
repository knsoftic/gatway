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
        $this->baseUrl    = rtrim(config('payfast.base_url'), '/');
        $this->merchantId = config('payfast.merchant_id');
        $this->securedKey = config('payfast.secured_key');
        $this->hashKey    = config('payfast.hash_key');
    }

    /**
     * Step 1: Get Access Token
     */
    public function getAccessToken(string $customerIp = null): ?array
    {
        $ip = $customerIp ?? request()->ip();

        $response = Http::asForm()->post("{$this->baseUrl}/token", [
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

        // Optional email (required for some wallets like easypaisa)
        if (!empty($data['customer_email_address'])) {
            $payload['customer_email_address'] = $data['customer_email_address'];
        }

        // Add secured hash if hash key is configured
        if ($this->hashKey) {
            $hashString = $payload['basket_id'] . '+' . $payload['txnamt'];
            $payload['secured_hash'] = $this->generateHash($hashString);
        }

        $response = Http::asForm()
            ->withToken($token)
            ->post("{$this->baseUrl}/customer/validate", $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('PayFast Validate Error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

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

        // Optional fields
        if (!empty($data['eci'])) {
            $payload['eci'] = $data['eci'];
        }

        // Add secured hash
        if ($this->hashKey) {
            $hashString = $payload['basket_id'] . '+' . $payload['txnamt'] . '+' . $payload['otp'];
            $payload['secured_hash'] = $this->generateHash($hashString);
        }

        $response = Http::asForm()
            ->withToken($token)
            ->post("{$this->baseUrl}/transaction", $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('PayFast Transaction Error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }

    /**
     * Check Transaction Status
     */
    public function getTransactionStatus(string $token, string $transactionId): ?array
    {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/transaction/{$transactionId}");

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('PayFast Status Check Error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }

    /**
     * Get list of banks
     */
    public function getBanks(string $token): ?array
    {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/list/banks");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get instruments for a bank
     */
    public function getInstruments(string $token, string $bankCode): ?array
    {
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/list/instruments", [
                'bank_code' => $bankCode,
            ]);

        if ($response->successful()) {
            return $response->json();
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
