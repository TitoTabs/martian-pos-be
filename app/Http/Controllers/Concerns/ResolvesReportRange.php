<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

trait ResolvesReportRange
{
    /**
     * Validate the period keyword and resolve it to a concrete [start, end]
     * range in the app timezone (Asia/Manila).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolveReportRange(Request $request, ReportService $reports): array
    {
        $period = $request->validate([
            'period' => ['sometimes', 'in:today,week,month,year'],
        ])['period'] ?? 'today';

        return $reports->range($period);
    }
}
