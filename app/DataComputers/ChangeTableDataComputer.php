<?php

namespace App\DataComputers;

use Carbon\CarbonPeriod;

class ChangeTableDataComputer implements DataComputer
{
    /**
     * get computed data for change table
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        $months = [];
        $period = CarbonPeriod::create(now()->subMonths(6), now());

        foreach ($period as $item)
            $months[] = $item->format('M');

        $months = array_values(array_unique($months));

        for ($i = 0; $i < 7; $i++) {
            if ($i === 0)
                continue;

            foreach ($cachedResponses as $response) {
                if (empty($data[$i]['current_clicks']))
                    $data[$i]['current_clicks'] = 0;

                if (empty($data[$i]['prev_clicks']))
                    $data[$i]['prev_clicks'] = 0;

                if (empty($data[$i]['current_impressions']))
                    $data[$i]['current_impressions'] = 0;

                if (empty($data[$i]['prev_impressions']))
                    $data[$i]['prev_impressions'] = 0;

                $data[$i]['current_clicks'] += $response['current'][$i]['count_clicks'] ?? 0;
                $data[$i]['prev_clicks'] += $response['previous'][$i]['count_clicks'] ?? 0;

                $data[$i]['current_impressions'] += $response['current'][$i]['count_impressions'] ?? 0;
                $data[$i]['prev_impressions'] += $response['previous'][$i]['count_impressions'] ?? 0;
            }

            if (empty($data[$i]['prev_clicks']))
                $data[$i]['prev_clicks'] = 1;

            if (empty($data[$i]['prev_impressions']))
                $data[$i]['prev_impressions'] = 1;

            $data[$i]['change_clicks'] =
                round(($data[$i]['current_clicks'] - $data[$i]['prev_clicks']) / $data[$i]['prev_clicks'], 2) * 100;

            $data[$i]['change_impressions'] =
                round(($data[$i]['current_impressions'] - $data[$i]['prev_impressions']) / $data[$i]['prev_impressions'], 2) * 100;

            $data[$i]['month'] = $months[$i];
        }

        return array_values($data);
    }
}
