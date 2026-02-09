<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'next_run_date' => 'date',
        'last_run_at'   => 'datetime',
    ];

    public function assetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }
}