<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class P3k extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'p3k_type_id', 'room_id', 'status', 'location_data'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "P3K {$eventName}")
            ->useLogName('aset');
    }

    protected $guarded = [];

    protected $casts = [
        'location_data' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo(P3kType::class, 'p3k_type_id');
    }

    public function inventories()
    {
        return $this->hasMany(P3kInventory::class);
    }
    
    public function room()
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
