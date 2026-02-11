<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistParameter extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];
}