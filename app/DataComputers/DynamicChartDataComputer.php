<?php

namespace App\DataComputers;

class DynamicChartDataComputer implements DataComputer
{
    /**
     * get computed data for dynamic chart
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array[]
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [
            'current' => [],
            'previous' => [],
        ];

        $currentCountImpressions = 0;
        $prevCountImpressions = 0;

        foreach ($cachedResponses as $cache) {
            foreach ($cache['current'] as $dayN => $current) {
                if (empty($data['current'][$dayN]))
                    $data['current'][$dayN] = 0;

                $data['current'][$dayN] += $current['count_clicks'];

                $currentCountImpressions += $current['count_impressions'];
            }

            foreach ($cache['previous'] as $dayN => $previous) {
                if (empty($data['previous'][$dayN]))
                    $data['previous'][$dayN] = 0;

                $data['previous'][$dayN] += $previous['count_clicks'];

                $prevCountImpressions += $previous['count_impressions'];
            }
        }

        $totalClicksCurrent = array_sum($data['current']);
        $totalClicksPrev = array_sum($data['previous']);

        $data['overview'] = [
            'current_count_clicks' => $totalClicksCurrent,
            'prev_count_clicks' => $totalClicksPrev,
            'change_clicks' => round((($totalClicksCurrent - $totalClicksPrev) / $totalClicksPrev) * 100, 2),

            'current_count_impressions' => $currentCountImpressions,
            'prev_count_impressions' => $prevCountImpressions,
            'change_impressions' => round((($currentCountImpressions - $prevCountImpressions) / $prevCountImpressions) * 100, 2),
        ];

        return $data;
    }
}
