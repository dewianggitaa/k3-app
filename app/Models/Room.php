<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Room extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['floor_id', 'name', 'code', 'pic_user_id'])
            ->setDescriptionForEvent(fn(string $eventName) => "Room {$eventName}")
            ->useLogName('master-data');
    }

    protected $fillable = [
        'floor_id',
        'name',
        'code',
        'coordinates',
        'pic_user_id',
        'color',
    ];

    protected $casts = [
        'coordinates' => 'json',
    ];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }
}