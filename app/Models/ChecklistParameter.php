<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ChecklistParameter extends Model
{
    use LogsActivity;
    protected $guarded = ['id'];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['asset_type', 'input_type', 'label', 'options', 'is_active', 'standard_value', 'order_index'])
            ->setDescriptionForEvent(fn(string $eventName) => "Checklist Parameter {$eventName}")
            ->useLogName('master-data');
    }
}