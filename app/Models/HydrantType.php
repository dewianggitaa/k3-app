<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HydrantType extends Model
{
    protected $fillable = ['name'];

    public function hydrants(): HasMany
    {
        return $this->hasMany(Hydrant::class);
    }
}