<?php

namespace App\DataComputers;

class DynamicStructureDataComputer implements DataComputer
{
    /**
     * get computed data for dynamic structure
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        foreach ($cachedResponses as $response) {
            foreach ($response as $position => $days) {
                foreach ($days as $key => $counts) {
                    if (empty($data[$position][$key]))
                        $data[$position][$key] = 0;

                    $data[$position][$key] += $counts['count_clicks'];
                }
            }
        }

        return $data;
    }
}
