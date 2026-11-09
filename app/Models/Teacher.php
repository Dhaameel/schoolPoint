<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'employee_number',
        'phone',
        'photo_path',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
