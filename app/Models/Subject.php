<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'code'];

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }
}
