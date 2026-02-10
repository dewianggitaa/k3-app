<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

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