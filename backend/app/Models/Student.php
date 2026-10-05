<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;
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
