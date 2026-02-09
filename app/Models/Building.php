<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

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