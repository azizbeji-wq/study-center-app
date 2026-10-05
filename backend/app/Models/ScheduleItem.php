<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleItem extends Model
{
    protected $fillable = ['day', 'time', 'subject', 'teacher', 'level', 'room', 'group_name'];
}
