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

    public function teachesClass(int $classId): bool
    {
        return SchoolClass::whereKey($classId)
            ->where(function ($query) {
                $query->where('class_teacher_id', $this->id)
                    ->orWhereHas('classSubjects', fn ($query) => $query->where('teacher_id', $this->id));
            })
            ->exists();
    }
}
