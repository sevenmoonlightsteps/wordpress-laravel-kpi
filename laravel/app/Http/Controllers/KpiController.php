<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\KpiMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KpiController extends Controller
{
    public function summary(): JsonResponse
    {
        $kpis = Kpi::all()->map(fn (Kpi $kpi) => [
            'id' => $kpi->id,
            'name' => $kpi->name,
            'slug' => $kpi->slug,
            'unit' => $kpi->unit,
            'latest_value' => $kpi->latestValue(),
        ]);

        return response()->json(['success' => true, 'data' => $kpis]);
    }

    public function history(Request $request, Kpi $kpi): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'integer|min:1|max:365',
            'order' => 'in:asc,desc',
        ]);

        $limit = $validated['limit'] ?? 30;
        $order = $validated['order'] ?? 'desc';

        $metrics = $kpi->metrics()
            ->orderBy('recorded_at', $order)
            ->limit($limit)
            ->get(['value', 'recorded_at'])
            ->map(fn (KpiMetric $m) => [
                'value' => $m->value,
                'recorded_at' => $m->recorded_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'kpi' => [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'slug' => $kpi->slug,
                    'unit' => $kpi->unit,
                ],
                'metrics' => $metrics,
            ],
        ]);
    }

    public function update(Request $request, Kpi $kpi): JsonResponse
    {
        try {
            $validated = $request->validate([
                'value' => 'required|numeric',
                'recorded_at' => 'sometimes|date|before_or_equal:now',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $metric = $kpi->metrics()->create([
            'value' => $validated['value'],
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $metric->id,
                'kpi_id' => $metric->kpi_id,
                'value' => $metric->value,
                'recorded_at' => $metric->recorded_at?->toIso8601String(),
            ],
        ], 201);
    }
}
