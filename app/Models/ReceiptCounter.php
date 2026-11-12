<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptCounter extends Model
{
    protected $fillable = [
        'date',
        'last_sequence',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
