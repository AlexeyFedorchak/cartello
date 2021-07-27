<?php

namespace App\DataFilters;

use App\BigQuery\IClient;
use App\Charts\Constants\ChartTable;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\Chart;
use App\Charts\Models\FilterOption;
use App\Sessions\Traits\ArabicAlphabet;
use App\Sessions\Traits\BrandSessionsRegex;
use App\Sessions\Traits\EnglishAlphabet;

class OpportunityTableDataFilter implements DataFilter
{
    use EnglishAlphabet, BrandSessionsRegex, ArabicAlphabet;

    /**
     * @var Chart
     */
    protected $chart;

    /**
     * default per page
     *
     * @var int
     */
    protected $perPage = 20;

    public function __construct(Chart $chart)
    {
        $this->chart = $chart;
    }

    /**
     * filter and sort paginated data
     *
     * @param int $page
     * @param array $filters
     * @param array $domains
     * @param string $sortBy
     * @param string $direction
     * @return array
     */
    public function filterAndSort(int $page, array $filters = [], array $domains = [], string $sortBy = 'opportunities', string $direction = 'desc'): array
    {
        $query = app(IClient::class)
            ->select(ChartTable::CHART_TABLE, [
                'SUM(clicks) as sum_clicks',
                'SUM(impressions) as sum_impressions',
                'AVG(position) as sum_position',
                'SAFE_SUBTRACT(SUM(impressions), SUM(clicks)) as sum_opportunities',
                'query',
            ])
            ->where('position >= ' . $this->getLowPosition())
            ->where('date <= CURRENT_DATE()');

        if (count($domains) > 0)
            $query->where('domain in (' . $this->getDomainList($domains) . ')');

        $query->openGroupCondition();
        foreach ($this->brandKeywords() as $keyword)
            $query->where('query not like "%' . $keyword . '%"', 'AND');

        $query->closeGroupCondition();

        if (strpos($this->chart->source_columns, 'arabic') !== false) {
            //include arabic chars
            $query->openGroupCondition();

            foreach ($this->getArabicChars() as $char)
                $query->where('query like "%' . $char . '%"', 'OR');

            $query->closeGroupCondition();

            //exclude english chars
            $query->openGroupCondition();

            foreach ($this->getEnglishChars() as $char)
                $query->where('query not like "%' . $char . '%"', 'AND');

            $query->closeGroupCondition();
        } else {
            //exclude arabic chars
            $query->openGroupCondition();

            foreach ($this->getArabicChars() as $char)
                $query->where('query not like "%' . $char . '%"', 'AND');

            $query->closeGroupCondition();

            //include english chars
            $query->openGroupCondition();

            foreach ($this->getEnglishChars() as $char)
                $query->where('query like "%' . $char . '%"', 'OR');

            $query->closeGroupCondition();
        }

        $query->groupBy('query');

        //by max values
        if (!empty($filters['max_clicks']) && is_numeric($filters['max_clicks']))
            $query->having('sum_clicks <= ' . $filters['max_clicks']);

        if (!empty($filters['max_opportunities']) && is_numeric($filters['max_opportunities']))
            $query->having('sum_opportunities <= ' . $filters['max_opportunities']);

        if (!empty($filters['max_impressions']) && is_numeric($filters['max_impressions']))
            $query->having('sum_impressions <= ' . $filters['max_impressions']);

        if (!empty($filters['max_position']) && is_numeric($filters['max_position']))
            $query->having('sum_position <= ' . $filters['max_position']);

        // by min values
        if (!empty($filters['min_clicks']) && is_numeric($filters['min_clicks']))
            $query->having('sum_clicks >= ' . $filters['min_clicks']);

        if (!empty($filters['min_opportunities']) && is_numeric($filters['min_opportunities']))
            $query->having('sum_opportunities >= ' . $filters['min_opportunities']);

        if (!empty($filters['min_impressions']) && is_numeric($filters['min_impressions']))
            $query->having('sum_impressions >= ' . $filters['min_impressions']);

        if (!empty($filters['min_position']) && is_numeric($filters['min_position']))
            $query->having('sum_position >= ' . $filters['min_position']);

        if (empty($sortBy))
            $sortBy = 'opportunities';

        if (empty($direction))
            $direction = 'desc';

        if ($sortBy === 'opportunities')
            $query->orderBy('sum_opportunities ' . $direction);

        if ($sortBy === 'impressions')
            $query->orderBy('impressions ' . $direction);

        if ($sortBy === 'clicks')
            $query->orderBy('clicks ' . $direction);

        if ($sortBy === 'position')
            $query->orderBy('position ' . $direction);

        if ($sortBy === 'query')
            $query->orderBy('query ' . $direction);

        return $query->limit($this->perPage, ($page - 1) * $this->perPage)
            ->get();
    }

    /**
     * get domain list
     *
     * @param array $domains
     * @return string
     */
    private function getDomainList(array $domains): string
    {
        return implode(
            ',',
            array_map(function ($domain) {
                return '"' . $domain . '"';
            }, $domains)
        );
    }

    /**
     * cache filter options
     *
     * @return void
     */
    public function cacheFilterOptions(): void
    {
        foreach (CachedDomainList::all() as $domain) {
            $clicks = $this->getMaxOptions('SUM(clicks)', $domain->domain);
            $impressions = $this->getMaxOptions('SUM(impressions)', $domain->domain);
            $opportunities = $this->getMaxOptions('SAFE_SUBTRACT(SUM(impressions), SUM(clicks))', $domain->domain);

            $count = $this->getMaxOptions('COUNT(*)', $domain->domain);

            $filterOptions = [
                'total' => $count['value'],
                'total_pages' => $count['value'] / $this->perPage,
                'per_page' => $this->perPage,
                'max_clicks' => $clicks['value'],
                'max_impressions' => $impressions['value'],
                'max_position' => $this->getHighPosition(),
                'max_opportunities' => $opportunities['value'],
            ];

            FilterOption::updateOrCreate(['chart_id' => $this->chart->id, 'domain' => $domain->domain], [
                'options' => json_encode($filterOptions),
            ]);
        }
    }

    /**
     * get max options -> without after processing data
     *
     * @param string $identifier
     * @param string $domain
     * @return array|null
     */
    private function getMaxOptions(string $identifier, string $domain): ?array
    {
        $query = app(IClient::class)
            ->select(ChartTable::CHART_TABLE, [
                $identifier . ' as value',
                'query',
            ])
            ->where('date <= CURRENT_DATE()')
            ->where('domain = "' . $domain . '"')
            ->where('position >= ' . $this->getLowPosition())
            ->where('position <= ' . $this->getHighPosition());

        $query->openGroupCondition();
        foreach ($this->brandKeywords() as $keyword)
            $query->where('query not like "%' . $keyword . '%"', 'AND');

        $query->closeGroupCondition();

        if (strpos($this->chart->source_columns, 'arabic') !== false) {
            //include arabic chars
            $query->openGroupCondition();

            foreach ($this->getArabicChars() as $char)
                $query->where('query like "%' . $char . '%"', 'OR');

            $query->closeGroupCondition();

            //exclude english chars
            $query->openGroupCondition();

            foreach ($this->getEnglishChars() as $char)
                $query->where('query not like "%' . $char . '%"', 'AND');

            $query->closeGroupCondition();
        } else {
            //exclude arabic chars
            $query->openGroupCondition();

            foreach ($this->getArabicChars() as $char)
                $query->where('query not like "%' . $char . '%"', 'AND');

            $query->closeGroupCondition();

            //include english chars
            $query->openGroupCondition();

            foreach ($this->getEnglishChars() as $char)
                $query->where('query like "%' . $char . '%"', 'OR');

            $query->closeGroupCondition();
        }

        $query->groupBy('query');
        $query->orderBy('value DESC');

        return $query->get()[0] ?? null;
    }

    /**
     * get low position
     *
     * @return int
     */
    private function getLowPosition(): int
    {
        return strpos($this->chart->slug, '2') === false ? 1 : 11;
    }

    /**
     * get high position
     *
     * @return int
     */
    private function getHighPosition(): int
    {
        return strpos($this->chart->slug, '2') === false ? 10 : 20;
    }
}
