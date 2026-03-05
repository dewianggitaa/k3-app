<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Inspection extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'schedule_date' => 'date',
        'due_date'      => 'date',
        'completed_at'  => 'datetime',
        'report_data'   => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['schedule_id', 'assetable_type', 'assetable_id', 'user_id', 'status', 'schedule_date', 'due_date', 'report_data'])
            ->setDescriptionForEvent(fn(string $eventName) => "Inspection {$eventName}")
            ->useLogName('jadwal-inspeksi');
    }

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