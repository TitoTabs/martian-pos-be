<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * A resolved reporting range for a business shift.
 *
 * `start`/`end` are precise timestamps used to filter `created_at`-based
 * records (POS sales) across the shift — which may cross midnight.
 * `dateStart`/`dateEnd` are the business dates (Y-m-d) used to filter
 * date-column records (expenses, manual sales) that are recorded per
 * business day rather than at a precise time.
 */
class ReportRange
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly string $dateStart,
        public readonly string $dateEnd,
    ) {
    }

    /** Timestamp bounds for created_at filtering. */
    public function timestamps(): array
    {
        return [$this->start, $this->end];
    }

    /** Business-date bounds for date-column filtering. */
    public function dates(): array
    {
        return [$this->dateStart, $this->dateEnd];
    }
}
