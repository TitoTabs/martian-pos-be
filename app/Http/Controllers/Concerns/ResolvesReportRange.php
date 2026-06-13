<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ResolvesReportRange
{
    /**
     * Validate period/custom-range params and resolve them to a concrete
     * [start, end] range. Supports the period keywords plus period=custom
     * with start_date/end_date.
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

        return $reports->resolveRange(
            $validated['period'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
        );
    }
}
