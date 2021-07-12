<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Sessions\Traits\ArabicAlphabet;
use App\Sessions\Traits\BrandSessionsRegex;

class OpportunityTableDataGrabber implements DataGrabber
{
    use BrandSessionsRegex, BigQueryTimeFormat, ArabicAlphabet;
    /**
     * chart instance
     *
     * @var Chart
     */
    protected $chart;

    public function __construct(Chart $chart)
    {
        $this->chart = $chart;
    }

    /**
     * get rows
     *
     * @return array
     */
    public function rows(): array
    {
        $rows = [];

        foreach (CachedDomainList::all() as $domain) {
            if ($this->chart->source_columns === 'page-1')
                $data = $this->getRow($domain->domain, 1, 10);

            if ($this->chart->source_columns === 'page-1-arabic')
                $data = $this->getRow($domain->domain, 1, 10, true);

            if ($this->chart->source_columns === 'page-2')
                $data = $this->getRow($domain->domain, 11, 20);

            if ($this->chart->source_columns === 'page-2-arabic')
                $data = $this->getRow($domain->domain, 11, 20, true);


            $rows[$domain->domain] = json_encode($data, JSON_UNESCAPED_UNICODE);

            CachedResponses::updateOrCreate([
                'chart_id' => $this->chart->id,
                'domain' => $domain->domain,
            ], [
                'response' => $rows[$domain->domain],
            ]);
        }

        return $rows;
    }

    /**
     * get row
     *
     * @param int $lowPosition
     * @param int $highPosition
     * @param string $domain
     * @return mixed
     */
    private function getRow(string $domain, int $lowPosition = 1, int $highPosition = 3, bool $isArabic = false): array
    {
        $query = app(IClient::class)
            ->select('searchanalytics', [
                'SUM(clicks) as clicks',
                'SUM(impressions) as impressions',
                'AVG(position) as position',
                'SAFE_SUBTRACT(SUM(impressions), SUM(clicks)) as opportunities',
                'query',
            ])
            ->where('position >= ' . $lowPosition)
            ->where('position <= ' . $highPosition)
            ->where('date <= CURRENT_DATE()')
            ->where('domain = "' . $domain . '"');

        $query->openGroupCondition();
        foreach ($this->brandKeywords() as $keyword)
            $query->where('query not like "%' . $keyword . '%"', 'OR');

        $query->closeGroupCondition();

        if ($isArabic) {
            $query->openGroupCondition();
            foreach ($this->getArabicChars() as $char)
                $query->where('query like "%' . $char . '%"', 'OR');

            $query->closeGroupCondition();
        }

        return $query->groupBy('query')
            ->orderBy('opportunities DESC')
            ->limit(50)
            ->get();
    }
}
