<?php

namespace App\DataFilters;

interface DataFilter
{
    public function filterAndSort(int $page, array $filters = [], array $domains = [], string $sortBy = 'impressions', string $direction = 'desc'): array;
    public function cacheFilterOptions(): void;
}
