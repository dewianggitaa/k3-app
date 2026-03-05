<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class P3kUsage extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['p3k_id', 'p3k_item_id', 'type', 'qty', 'reporter_name', 'department_id', 'user_id', 'notes'])
            ->setDescriptionForEvent(fn(string $eventName) => "P3K Usage {$eventName}")
            ->useLogName('aset');
    }
}
