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
     * @return array
     */
    public function filterAndSort(int $page, array $filters = [], array $domains = [], string $sortBy = 'opportunities'): array
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

        if (!empty($filters['clicks']) && is_numeric($filters['clicks']))
            $query->having('sum_clicks <= ' . $filters['clicks']);

        if (!empty($filters['opportunities']) && is_numeric($filters['opportunities']))
            $query->having('sum_opportunities <= ' . $filters['opportunities']);

        if (!empty($filters['impressions']) && is_numeric($filters['impressions']))
            $query->having('sum_impressions <= ' . $filters['impressions']);

        if (!empty($filters['position']) && is_numeric($filters['position']))
            $query->having('sum_position <= ' . $filters['position']);

        if ($sortBy === 'opportunities')
            $query->orderBy('sum_opportunities DESC');

        if ($sortBy === 'impressions')
            $query->orderBy('impressions DESC');

        if ($sortBy === 'clicks')
            $query->orderBy('clicks DESC');

        if ($sortBy === 'position')
            $query->orderBy('position DESC');

        if ($sortBy === 'query')
            $query->orderBy('query DESC');

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
