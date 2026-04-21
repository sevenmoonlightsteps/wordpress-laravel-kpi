<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'unit', 'description'])]
class Kpi extends Model
{
    use HasFactory;

    /**
     * Get the metrics for this KPI.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(KpiMetric::class);
    }

    /**
     * Get the most recent metric value for this KPI.
     */
    public function latestValue(): ?float
    {
        return $this->metrics()
            ->orderBy('recorded_at', 'desc')
            ->limit(1)
            ->pluck('value')
            ->first();
    }
}
