<?php

namespace App\DataComputers;

class AVGPositionDataComputer implements DataComputer
{
    /**
     * get computed data for avg position chart
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
                    if (empty($data[$period][$dayN]))
                        $data[$period][$dayN] = $dayV['avg_position'];

                    $data[$period][$dayN] = ($data[$period][$dayN] + $dayV['avg_position']) / 2;
                }
            }
        }

        return $data;
    }
}
