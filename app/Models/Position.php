<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    // Pastikan fillable sesuai kolom di migration
    protected $fillable = ['name'];

    /**
     * Relasi: Position memiliki banyak User
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}