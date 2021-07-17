<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;

class DynamicStructureDataGrabberPages implements DataGrabber
{
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
            $page_1 = $this->getRow($domain->domain, 1, 10);
            $page_2 = $this->getRow($domain->domain, 11, 20);
            $page_3 = $this->getRow($domain->domain, 21, 30);
            $page_4_more = $this->getRow($domain->domain, 30, PHP_INT_MAX);

            $rows[$domain->domain] = [
                'page_1' => $page_1,
                'page_2' => $page_2,
                'page_3' => $page_3,
                'page_4_and_more' => $page_4_more,
            ];

            CachedResponses::updateOrCreate([
                'chart_id' => $this->chart->id,
                'domain' => $domain->domain,
            ], [
                'response' => json_encode($rows[$domain->domain]),
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
    private function getRow(string $domain, int $lowPosition = 1, int $highPosition = 3): array
    {
        return app(IClient::class)
            ->select('searchanalytics', ['SUM(clicks) as count_clicks', 'SUM(impressions) as count_impressions', 'date'])
            ->where('position >= ' . $lowPosition)
            ->where('position <= ' . $highPosition)
            ->where('date <= CURRENT_DATE()')
            ->where('domain = "' . $domain . '"')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
