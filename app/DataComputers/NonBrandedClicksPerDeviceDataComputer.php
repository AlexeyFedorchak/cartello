<?php

namespace App\DataComputers;

class NonBrandedClicksPerDeviceDataComputer implements DataComputer
{
    /**
     * get computed data for non brand clicks per device
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        foreach ($cachedResponses as $domainV)
            foreach ($domainV as $deviceN => $deviceV)
                foreach ($deviceV as $period => $periodV) {
                    if (!is_array($periodV))
                        continue;

                    foreach ($periodV as $dayN => $dayV) {
                        if (!isset($data[$deviceN . '-' . $period][$dayN]))
                            $data[$deviceN . '-' . $period][$dayN] = 0;

                        $data[$deviceN . '-' . $period][$dayN] += $dayV['count_clicks'];
                    }
                }

        return $data;
    }
}
