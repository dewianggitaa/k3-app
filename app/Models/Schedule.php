<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Schedule extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->setDescriptionForEvent(fn(string $eventName) => "Schedule {$eventName}")
            ->useLogName('jadwal-inspeksi');
    }

    protected $guarded = ['id'];


    protected $casts = [
        'next_run_date' => 'date',
        'last_run_at'   => 'datetime',
    ];

    

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function buildings()
    {
        return $this->belongsToMany(Building::class, 'building_schedule');
    }

}