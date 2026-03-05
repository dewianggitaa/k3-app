<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class P3kInventory extends Model
{
    use LogsActivity;

    protected $guarded = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['p3k_id', 'p3k_item_id', 'current_qty'])
            ->setDescriptionForEvent(fn(string $eventName) => "P3K Inventory {$eventName}")
            ->useLogName('aset');
    }

    public function item()
    {
        return $this->belongsTo(P3kItem::class, 'p3k_item_id');
    }
}
