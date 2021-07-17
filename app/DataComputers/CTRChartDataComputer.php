<?php

namespace App\DataComputers;

class CTRChartDataComputer implements DataComputer
{
    /**
     * compute data for ctr dynamic chart
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
                foreach ($days as $dayN => $ctr) {
                    if (empty($data[$key][$dayN]))
                        $data[$key][$dayN] = 0;

                    $data[$key][$dayN] = ($ctr + $data[$key][$dayN]) / 2;
                }

        return $data;
    }
}
