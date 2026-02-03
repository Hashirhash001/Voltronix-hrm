<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'holiday_name',
        'holiday_date',
        'type',
        'entity_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function entity()
    {
        return $this->belongsTo(Entity::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    public function scopeForEntity($query, $entityId)
    {
        return $query->where(function($q) use ($entityId) {
            $q->whereNull('entity_id') // Public holidays
              ->orWhere('entity_id', $entityId); // Entity-specific
        });
    }

    public function scopeUpcoming($query)
    {
        return $query->where('holiday_date', '>=', Carbon::today());
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('holiday_date', [$startDate, $endDate]);
    }

    // Helper Methods
    public static function isHoliday($date, $entityId = null)
    {
        $query = static::active()
            ->where('holiday_date', $date);

        if ($entityId) {
            $query->forEntity($entityId);
        }

        return $query->exists();
    }

    public static function getHolidayName($date, $entityId = null)
    {
        $query = static::active()
            ->where('holiday_date', $date);

        if ($entityId) {
            $query->forEntity($entityId);
        }

        return $query->first()?->holiday_name;
    }

    public function isPast()
    {
        return $this->holiday_date->isPast();
    }

    public function isToday()
    {
        return $this->holiday_date->isToday();
    }

    public function isUpcoming()
    {
        return $this->holiday_date->isFuture();
    }
}
