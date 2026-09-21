<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = ['name', 'subject', 'phone', 'email', 'weekly_lessons'];

    public function enrollments()
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }
}
