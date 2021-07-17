<?php

namespace App\DataComputers;

interface DataComputer
{
    public function compute(array $cachedResponses, array $domains = []): array;
}
