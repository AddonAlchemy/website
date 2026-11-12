<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class DailySeries
{
    /**
     * @param  Collection<string, int|float|string>  $totalsByDay
     * @return array{labels: list<string>, values: list<float>}
     */
    public static function fill(Collection $totalsByDay, int $days, float $divisor = 1.0): array
    {
        $labels = [];
        $values = [];
        $start = CarbonImmutable::today()->subDays($days - 1);

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->addDays($offset);
            $labels[] = $day->format('d.m.');
            $values[] = round(((float) ($totalsByDay[$day->toDateString()] ?? 0)) / $divisor, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
