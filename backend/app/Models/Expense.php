<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'is_recurring' => 'boolean',
        'date' => 'date:Y-m-d',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
