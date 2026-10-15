<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayfastTransaction extends Model
{
    protected $fillable = [
        'basket_id',
        'transaction_id',
        'amount',
        'description',
        'customer_name',
        'customer_email',
        'customer_mobile',
        'account_type_id',
        'status',
        'status_code',
        'status_msg',
        'qr_token',
        'api_response',
        'paid_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'api_response' => 'array',
        'paid_at'      => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
