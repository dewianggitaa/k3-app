<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P3k extends Model
{
    protected $guarded = [];

    protected $casts = [
        'location_data' => 'array',
    ];

    public function p3k_type()
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
}
