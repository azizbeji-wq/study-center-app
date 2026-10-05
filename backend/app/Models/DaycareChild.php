<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DaycareChild extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
