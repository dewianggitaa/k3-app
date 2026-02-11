<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inspection extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'schedule_date' => 'date',
        'due_date'      => 'date',
        'completed_at'  => 'datetime',
        'report_data'   => 'array',
    ];


    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function assetable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}