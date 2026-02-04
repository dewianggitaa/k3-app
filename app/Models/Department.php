<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    // Pastikan fillable sesuai kolom di migration
    protected $fillable = ['name']; 

    /**
     * Relasi: Department memiliki banyak User
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}