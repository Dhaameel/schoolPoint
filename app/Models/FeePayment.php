<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    protected $fillable = [
        'fee_id',
        'reference',
        'receipt_number',
        'amount',
        'type',
        'reversal_of_id',
        'reason',
        'paid_at',
        'method',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversedBy()
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }
}
