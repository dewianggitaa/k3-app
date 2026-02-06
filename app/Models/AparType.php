<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AparType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function apars()
    {
        return $this->hasMany(Apar::class);
    }
}