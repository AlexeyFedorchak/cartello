<?php

namespace App\DataComputers;

class NonBrandedClicksDataComputer implements DataComputer
{
    /**
     * get computed data for non branded clicks chart
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        foreach ($cachedResponses as $response) {
            foreach ($response as $period => $days) {
                foreach ($days as $dayN => $dayV) {
                    if (!isset($data[$period][$dayN]))
                        $data[$period][$dayN] = 0;

                    $data[$period][$dayN] += $dayV['count_clicks'];
                }
            }
        }

        return $data;
    }
}
