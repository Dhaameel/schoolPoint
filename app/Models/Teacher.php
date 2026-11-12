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

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function classTeacherClasses()
    {
        return $this->hasMany(SchoolClass::class, 'class_teacher_id');
    }
}
