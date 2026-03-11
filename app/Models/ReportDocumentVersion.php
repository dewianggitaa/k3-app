<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class ReportDocumentVersion extends Model
{
    use LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['asset_type', 'document_code', 'attachment_number', 'title', 'effective_date', 'revision_number', 'status', 'notes'])
            ->setDescriptionForEvent(fn(string $eventName) => "Report Document Version {$eventName}")
            ->useLogName('master-data');
    }

    // ─── Scopes ───────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('asset_type', $type);
    }

    // ─── Static Helpers ───────────────────────────────────────
    public static function getActiveForType(string $type): ?self
    {
        return static::active()->forType($type)->first();
    }

    /**
     * Check if any active version needs renewal (>= 3 years old)
     */
    public static function hasRenewalNeeded(): bool
    {
        return static::active()
            ->whereNotNull('effective_date')
            ->where('effective_date', '<=', Carbon::now()->subYears(3))
            ->exists();
    }

    /**
     * Get types that need renewal
     */
    public static function typesNeedingRenewal(): array
    {
        return static::active()
            ->whereNotNull('effective_date')
            ->where('effective_date', '<=', Carbon::now()->subYears(3))
            ->pluck('asset_type')
            ->toArray();
    }

    // ─── Instance Methods ─────────────────────────────────────
    public function activate(): void
    {
        // Deactivate current active version for this asset_type
        static::active()
            ->forType($this->asset_type)
            ->update(['status' => 'inactive']);

        // Activate this version
        $this->update([
            'status' => 'active',
            'effective_date' => Carbon::today(),
        ]);
    }

    public function needsRenewal(): bool
    {
        return $this->status === 'active'
            && $this->effective_date
            && $this->effective_date->diffInYears(Carbon::now()) >= 3;
    }
}
