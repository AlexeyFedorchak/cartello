<?php

namespace App\DataComputers;

class OpportunityTableDataComputer implements DataComputer
{
    /**
     * group data for given set of domain for opportunity table chart
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        $data = [];

        foreach ($cachedResponses as $response) {
            $data = array_merge($data, $response);
        }

        return $data;
    }
}
