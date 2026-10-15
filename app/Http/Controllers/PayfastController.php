<?php

namespace App\Http\Controllers;

use App\Models\PayfastTransaction;
use App\Services\PayfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayfastController extends Controller
{
    protected PayfastService $payfast;

    public function __construct(PayfastService $payfast)
    {
        $this->payfast = $payfast;
    }

    /**
     * Show Settings & API Keys configuration page
     */
    public function settings()
    {
        $settings = PayfastService::getAllSettings();
        return view('payfast.settings', compact('settings'));
    }

    /**
     * Save API Keys & Customization settings
     */
    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'merchant_id'  => 'nullable|string|max:100',
            'secured_key'  => 'nullable|string|max:255',
            'hash_key'     => 'nullable|string|max:255',
            'base_url'     => 'required|url',
            'brand_name'   => 'nullable|string|max:100',
            'brand_color'  => 'nullable|string|max:20',
            'accent_color' => 'nullable|string|max:20',
            'logo_url'     => 'nullable|string|max:255',
        ]);

        PayfastService::saveSettings($validated);

        return redirect()->route('payfast.settings')
            ->with('success', 'API settings and branding updated successfully!');
    }

    /**
     * Test API Connection
     */
    public function testConnection(Request $request)
    {
        $result = $this->payfast->testConnection(
            $request->input('merchant_id'),
            $request->input('secured_key'),
            $request->input('base_url')
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->withErrors(['connection_error' => $result['message']]);
    }

    /**
     * Show the create payment form (Admin/Merchant side)
     */
    public function create()
    {
        $settings = PayfastService::getAllSettings();
        return view('payfast.create', compact('settings'));
    }

    /**
     * Generate a new QR payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
        ]);

        $transaction = PayfastTransaction::create([
            'basket_id'   => 'QR-' . strtoupper(Str::random(10)) . '-' . time(),
            'amount'      => $validated['amount'],
            'description' => $validated['description'],
            'qr_token'    => Str::uuid()->toString(),
            'status'      => 'pending',
        ]);

        return redirect()->route('payfast.qr.show', $transaction->qr_token);
    }

    /**
     * Show QR code page for customer to scan
     */
    public function showQr(string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();

        if ($transaction->isCompleted()) {
            return redirect()->route('payfast.success', $transaction->qr_token);
        }

        // Generate the payment URL that QR will point to
        $paymentUrl = route('payfast.pay', $transaction->qr_token);

        return view('payfast.qr', compact('transaction', 'paymentUrl'));
    }

    /**
     * Show payment form (after customer scans QR)
     */
    public function payForm(string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();

        if ($transaction->isCompleted()) {
            return redirect()->route('payfast.success', $transaction->qr_token);
        }

        if ($transaction->isFailed()) {
            return redirect()->route('payfast.failed', $transaction->qr_token);
        }

        return view('payfast.pay', compact('transaction'));
    }

    /**
     * Step 1: Validate customer & send OTP
     */
    public function validateCustomer(Request $request, string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();

        $validated = $request->validate([
            'customer_mobile' => 'required|string|min:11|max:13',
            'customer_name'   => 'nullable|string|max:100',
            'customer_email'  => 'nullable|email|max:100',
            'account_type_id' => 'required|string',
        ]);

        // Get access token
        $tokenResponse = $this->payfast->getAccessToken();
        if (!$tokenResponse || empty($tokenResponse['token'])) {
            return back()->withErrors(['error' => 'Payment service unavailable. Please try again.']);
        }

        $accessToken = $tokenResponse['token'];

        // Validate customer & send OTP
        $validateResponse = $this->payfast->customerValidate($accessToken, [
            'basket_id'          => $transaction->basket_id,
            'txnamt'             => number_format($transaction->amount, 2, '.', ''),
            'order_date'         => now()->format('Y-m-d'),
            'customer_mobile_no' => $validated['customer_mobile'],
            'account_type_id'    => $validated['account_type_id'],
            'customer_email_address' => $validated['customer_email'] ?? null,
        ]);

        if (!$validateResponse) {
            return back()->withErrors(['error' => 'Could not validate your details. Please check and try again.']);
        }

        // Update transaction
        $transaction->update([
            'customer_name'   => $validated['customer_name'] ?? null,
            'customer_email'  => $validated['customer_email'] ?? null,
            'customer_mobile' => $validated['customer_mobile'],
            'account_type_id' => $validated['account_type_id'],
            'transaction_id'  => $validateResponse['transaction_id'] ?? null,
            'status'          => 'processing',
            'api_response'    => $validateResponse,
        ]);

        // Store token in session for next step
        session([
            "payfast_token_{$qrToken}"  => $accessToken,
            "payfast_eci_{$qrToken}"    => $validateResponse['eci'] ?? null,
            "payfast_txn_id_{$qrToken}" => $validateResponse['transaction_id'] ?? null,
        ]);

        // Check if OTP is required
        $otpRequired = $validateResponse['otp_required'] ?? true;

        if (!$otpRequired || ($validateResponse['code'] ?? '') == '850') {
            // OTP not required — issuer manages it
            return redirect()->route('payfast.pay', $qrToken)
                ->with('otp_managed', true)
                ->with('step', 'confirm');
        }

        return redirect()->route('payfast.pay', $qrToken)
            ->with('step', 'otp')
            ->with('otp_message', $validateResponse['message'] ?? 'OTP has been sent to your mobile.');
    }

    /**
     * Step 2: Submit OTP and charge
     */
    public function processPayment(Request $request, string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();

        $validated = $request->validate([
            'otp' => 'required|string|min:4|max:8',
        ]);

        $accessToken   = session("payfast_token_{$qrToken}");
        $transactionId = session("payfast_txn_id_{$qrToken}");

        if (!$accessToken || !$transactionId) {
            return back()->withErrors(['error' => 'Session expired. Please start over.']);
        }

        $chargeResponse = $this->payfast->initiateTransaction($accessToken, [
            'basket_id'          => $transaction->basket_id,
            'txnamt'             => number_format($transaction->amount, 2, '.', ''),
            'order_date'         => now()->format('Y-m-d'),
            'customer_mobile_no' => $transaction->customer_mobile,
            'account_type_id'    => $transaction->account_type_id,
            'otp'                => $validated['otp'],
            'transaction_id'     => $transactionId,
            'eci'                => session("payfast_eci_{$qrToken}"),
        ]);

        if (!$chargeResponse) {
            $transaction->update(['status' => 'failed', 'status_msg' => 'Transaction failed']);
            return redirect()->route('payfast.failed', $qrToken);
        }

        $statusCode = $chargeResponse['code'] ?? ($chargeResponse['status_code'] ?? null);

        // Update transaction with response
        $transaction->update([
            'transaction_id' => $chargeResponse['transaction_id'] ?? $transactionId,
            'status_code'    => $statusCode,
            'status_msg'     => $chargeResponse['status_msg'] ?? $chargeResponse['message'] ?? '',
            'api_response'   => $chargeResponse,
            'status'         => in_array($statusCode, ['00', '000', '0']) ? 'completed' : 'failed',
            'paid_at'        => in_array($statusCode, ['00', '000', '0']) ? now() : null,
        ]);

        // Clear session
        session()->forget([
            "payfast_token_{$qrToken}",
            "payfast_eci_{$qrToken}",
            "payfast_txn_id_{$qrToken}",
        ]);

        if ($transaction->isCompleted()) {
            return redirect()->route('payfast.success', $qrToken);
        }

        return redirect()->route('payfast.failed', $qrToken);
    }

    /**
     * Payment success page
     */
    public function success(string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();
        return view('payfast.success', compact('transaction'));
    }

    /**
     * Payment failed page
     */
    public function failed(string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();
        return view('payfast.failed', compact('transaction'));
    }

    /**
     * Check transaction status (AJAX polling)
     */
    public function checkStatus(string $qrToken)
    {
        $transaction = PayfastTransaction::where('qr_token', $qrToken)->firstOrFail();

        return response()->json([
            'status'     => $transaction->status,
            'status_msg' => $transaction->status_msg,
            'paid_at'    => $transaction->paid_at?->format('d M Y, h:i A'),
        ]);
    }
}
