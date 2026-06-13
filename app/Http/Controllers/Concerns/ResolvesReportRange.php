<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ResolvesReportRange
{
    /**
     * Resolve the requested range to a concrete [start, end] in the app
     * timezone (Asia/Manila). Supports the period keywords plus an explicit
     * custom range via start_date/end_date — purely a query filter; it does
     * not change any timezone configuration.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveReportRange(Request $request, ReportService $reports): array
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'in:today,week,month,year,custom'],
            'start_date' => ['sometimes', 'required_with:end_date', 'date'],
            'end_date' => ['sometimes', 'required_with:start_date', 'date', 'after_or_equal:start_date'],
        ]);

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            return [
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
            ];
        }

        return $reports->range($validated['period'] ?? 'today');
    }
}
