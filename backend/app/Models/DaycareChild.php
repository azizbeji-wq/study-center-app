<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DaycareChild extends Model
{
    protected $fillable = ['name', 'age', 'parent_name', 'phone', 'schedule', 'status'];
}
