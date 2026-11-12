<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_id',
        'admission_number',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'guardian_name',
        'guardian_phone',
        'photo_path',
        'class_id',
        'status',
        'student_year',
        'student_sequence',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
