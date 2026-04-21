<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kpi_id', 'value', 'recorded_at'])]
class KpiMetric extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the KPI this metric belongs to.
     */
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }
}
