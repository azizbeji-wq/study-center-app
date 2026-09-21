<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSubjectEnrollment extends Model
{
    protected $fillable = ['student_id', 'subject_name', 'teacher_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
