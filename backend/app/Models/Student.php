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

    /**
     * Groupes auxquels cet élève appartient (table pivot group_student).
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_student')
                    ->withTimestamps();
    }

    /**
     * Paiements associés à cet élève.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
