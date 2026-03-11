<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Hydrant extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'hydrant_type_id', 'room_id', 'status', 'location_data'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Hydrant {$eventName}")
            ->useLogName('aset');
    }

    protected $fillable = [
        'code',
        'hydrant_type_id',
        'room_id',
        'status',
        'location_data'
    ];

    protected $casts = [
        'location_data' => 'array',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(HydrantType::class, 'hydrant_type_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function inspections()
    {
        return $this->morphMany(Inspection::class, 'assetable');
    }

    public function latest_inspection()
    {
        return $this->morphOne(Inspection::class, 'assetable')->latestOfMany();
    }
}