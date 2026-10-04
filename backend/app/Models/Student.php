<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'name',
        'level',
        'group_name',
        'phone',
        'status',
        'parent_phone',
        'is_remise',
        'remise',
        'discount_type',
        'base_fee'
    ];

    public function enrollments()
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
