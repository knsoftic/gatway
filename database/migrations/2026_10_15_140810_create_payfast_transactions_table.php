<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payfast_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('basket_id')->unique();
            $table->string('transaction_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->string('account_type_id')->nullable();
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->string('status_code')->nullable();
            $table->string('status_msg')->nullable();
            $table->string('qr_token')->unique(); // unique token for QR URL
            $table->json('api_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payfast_transactions');
    }
};
