<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Apar extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'apar_type_id', 'room_id', 'status', 'weight', 'last_refilled_at', 'expired_at', 'location_data'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Apar {$eventName}")
            ->useLogName('aset');
    }

    protected $fillable = [
        'code',
        'apar_type_id',
        'room_id',
        'status',
        'weight',
        'last_refilled_at',
        'expired_at',
        'location_data',
    ];

    protected $casts = [
        'location_data' => 'array',
        'last_refilled_at' => 'date',
        'expired_at' => 'date',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AparType::class, 'apar_type_id');
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