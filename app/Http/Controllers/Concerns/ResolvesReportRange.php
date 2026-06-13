<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ReportService;
use App\Support\ReportRange;
use Illuminate\Http\Request;

trait ResolvesReportRange
{
    /**
     * Resolve the requested range to a business-shift ReportRange in the app
     * timezone (Asia/Manila). Supports period keywords or an explicit custom
     * date range, plus an optional shift window (start_time/end_time, default
     * 21:00 → 03:00) so sales after midnight count under the prior business
     * day. Purely a query filter — no timezone configuration is changed.
     */
    protected function resolveReportRange(Request $request, ReportService $reports): ReportRange
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'in:today,week,month,year,custom'],
            'start_date' => ['sometimes', 'required_with:end_date', 'date'],
            'end_date' => ['sometimes', 'required_with:start_date', 'date', 'after_or_equal:start_date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
        ]);

        $startTime = $validated['start_time'] ?? ReportService::DEFAULT_SHIFT_START;
        $endTime = $validated['end_time'] ?? ReportService::DEFAULT_SHIFT_END;

        if (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            return $reports->customRange($validated['start_date'], $validated['end_date'], $startTime, $endTime);
        }

        return $reports->range($validated['period'] ?? 'today', $startTime, $endTime);
    }
}
