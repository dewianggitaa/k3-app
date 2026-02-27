<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P3kTypeItem extends Model
{
    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(P3kItem::class, 'p3k_item_id'); 
    }
}
