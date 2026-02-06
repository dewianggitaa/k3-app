<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hydrant extends Model
{
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

    public function hydrant_type(): BelongsTo
    {
        return $this->belongsTo(HydrantType::class, 'hydrant_type_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}