<?php

namespace App\DataComputers;

class BrandedNonBrandedClicksDataComputer implements DataComputer
{
    /**
     * get computed data for branded-non-branded dynamic charts
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        foreach ($cachedResponses as $response)
            foreach ($response as $key => $days)
                foreach ($days as $dayN => $day) {
                    if (empty($data[$key][$dayN]))
                        $data[$key][$dayN] = 0;

                    $data[$key][$dayN] += $day['count_clicks'];
                }

        return $data;
    }
}
