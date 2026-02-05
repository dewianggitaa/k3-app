<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P3kType extends Model
{
    protected $guarded = [];

    public function items()
    {
        return $this->belongsToMany(P3kItem::class, 'p3k_type_items')
                    ->withPivot('quantity');
    }
}