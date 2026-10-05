<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function daycareChild()
    {
        return $this->belongsTo(DaycareChild::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
