<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apar extends Model
{
    use HasFactory;

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

    /**
     * Casting attributes.
     * location_data otomatis jadi array/object di JS
     */
    protected $casts = [
        'location_data' => 'array',
        'last_refilled_at' => 'date',
        'expired_at' => 'date',
    ];

    /**
     * Relasi ke Tipe APAR (CO2, Powder, Foam, dll)
     */
    public function apar_type(): BelongsTo
    {
        return $this->belongsTo(AparType::class);
    }

    /**
     * Relasi ke Ruangan
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}