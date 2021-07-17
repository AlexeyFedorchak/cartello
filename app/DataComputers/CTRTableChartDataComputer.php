<?php

namespace App\DataComputers;

class CTRTableChartDataComputer implements DataComputer
{
    /**
     * reorder data for ctr table
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        foreach ($cachedResponses as $domainN => $response) {
            foreach ($response['current'] as $weekN => $weekValue) {
                $data[] = [
                    'week' => $response['weekly_time_row'][$weekN],
                    'domain' => $domains[$domainN],
                    'ctr' => $weekValue,
                    'weekN' => $weekN,
                ];
            }
        }

        usort($data, function ($a, $b) {
            if ($a['weekN'] > $b['weekN']) return 1;
            if ($a['weekN'] < $b['weekN']) return -1;

            return 0;
        });

        return $data;
    }
}
