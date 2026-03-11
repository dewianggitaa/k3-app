<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Contracts\Activity;

class P3kUsage extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    public function p3k()
    {
        return $this->belongsTo(P3k::class, 'p3k_id');
    }

    public function p3kItem()
    {
        return $this->belongsTo(P3kItem::class, 'p3k_item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['qty']) // Hanya catat qty secara default, info lain ditambahkan manual
            ->setDescriptionForEvent(function (string $eventName) {
                if ($eventName === 'created') {
                    $action = $this->type === 'in' ? 'Penambahan Stok P3K' : 'Penggunaan Item P3K';
                    return "{$action} oleh {$this->reporter_name}";
                }
                return "Aktivitas P3K {$eventName}";
            })
            ->useLogName('aset');
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        // Jangan gunakan user auth, karena repoter_name sudah ada di deskripsi
        $activity->causer_id = null;
        $activity->causer_type = null;

        if ($eventName === 'created') {
            $properties = $activity->properties->toArray();
            $attributes = $properties['attributes'] ?? [];

            // Tambahkan informasi yang lebih mudah dibaca manusia
            $attributes['kode_p3k']  = $this->p3k->code ?? '-';
            $attributes['nama_item'] = $this->p3kItem->name ?? '-';
            
            if (!empty($this->notes)) {
                $attributes['catatan'] = $this->notes;
            }
            $attributes['pelapor'] = $this->reporter_name;

            $properties['attributes'] = $attributes;
            $activity->properties = collect($properties);
        }
    }
}
