<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentIdCounter extends Model
{
    protected $fillable = ['year', 'last_sequence'];
}
