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
    protected $perPage = 50;

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
    public function filterAndSort(int $page, array $filters = [], array $domains = [], string $sortBy = 'impressions'): array
    {
        $query = app(IClient::class)
            ->select(ChartTable::CHART_TABLE, [
                'SUM(clicks) as clicks',
                'SUM(impressions) as impressions',
                'AVG(position) as position',
                'SAFE_SUBTRACT(SUM(impressions), SUM(clicks)) as opportunities',
                'query',
            ])
            ->where('position >= ' . $this->getLowPosition())
            ->where('position <= ' . $this->getHighPosition())
            ->where('date <= CURRENT_DATE()');

        if (count($domains) > 0)
            $query->where('domain in (' . implode(',', $domains) . ')');

        if (!empty($filters['clicks']) && is_numeric($filters['clicks']))
            $query->where('clicks <= ' . $filters['clicks']);

        if (!empty($filters['opportunities']) && is_numeric($filters['opportunities']))
            $query->where('opportunities <= ' . $filters['opportunities']);

        if (!empty($filters['impressions']) && is_numeric($filters['impressions']))
            $query->where('impressions <= ' . $filters['impressions']);

        if (!empty($filters['position']) && is_numeric($filters['position']))
            $query->where('position <= ' . $filters['position']);

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
     * cache filter options
     *
     * @return void
     */
    public function cacheFilterOptions(): void
    {
        foreach (CachedDomainList::all() as $domain) {
            $query = app(IClient::class)
                ->select(ChartTable::CHART_TABLE, [
                    'SUM(clicks) as clicks',
                    'SUM(impressions) as impressions',
                    'AVG(position) as position',
                    'SAFE_SUBTRACT(SUM(impressions), SUM(clicks)) as opportunities',
                    'query',
                ])
                ->where('date <= CURRENT_DATE()')
                ->where('domain = "' . $domain->domain . '"')
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

            $items = $query->get();

            $filterOptions = [
                'total' => count($items),
                'total_pages' => count($items) / $this->perPage,
                'per_page' => $this->perPage,
                'max_clicks' => collect($items)->pluck('clicks')->max(),
                'max_impressions' => collect($items)->pluck('impressions')->max(),
                'max_position' => $this->getHighPosition(),
                'max_opportunities' => collect($items)->pluck('opportunities')->max(),
            ];

            FilterOption::updateOrCreate(['chart_id' => $this->chart->id, 'domain' => $domain->domain], [
                'options' => json_encode($filterOptions),
            ]);
        }
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
