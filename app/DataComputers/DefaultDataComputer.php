<?php

namespace App\DataComputers;

class DefaultDataComputer implements DataComputer
{
    /**
     * default computer for cached data which no need to recompute
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function compute(array $cachedResponses, array $domains = []): array
    {
        return $cachedResponses;
    }
}
