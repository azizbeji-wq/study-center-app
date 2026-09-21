<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = ['name', 'level', 'phone', 'status'];

    public function enrollments()
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }
}
