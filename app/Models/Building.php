<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Building extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->setDescriptionForEvent(fn(string $eventName) => "Building {$eventName}")
            ->useLogName('master-data');
    }

    protected $fillable = [
        'name',
        'code',
    ];

    public function floors()
    {
        return $this->hasMany(Floor::class);
    }
    
    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Floor::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}