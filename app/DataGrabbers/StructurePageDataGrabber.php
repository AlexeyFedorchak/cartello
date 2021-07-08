<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;

class StructurePageDataGrabber implements DataGrabber
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
            $page_1 = collect($this->getRow($domain->domain, 1, 10))
                ->pluck('count_clicks')
                ->sum();

            $page_2 = collect($this->getRow($domain->domain, 11, 20))
                ->pluck('count_clicks')
                ->sum();

            $page_3 = collect($this->getRow($domain->domain, 21, 30))
                ->pluck('count_clicks')
                ->sum();

            $page_4 = collect($this->getRow($domain->domain, 30, PHP_INT_MAX))
                ->pluck('count_clicks')
                ->sum();

            $total = $page_1 + $page_2 + $page_3 + $page_4;

            $rows[$domain->domain] = [
                'page_1' => round(($page_1 / $total) * 100, 1),
                'page_2' => round(($page_2 / $total) * 100, 1),
                'page_3' => round(($page_3 / $total) * 100, 1),
                'page_4' => round(($page_4 / $total) * 100, 1),
                'page_1_numeric' => $page_1,
                'page_2_numeric' => $page_2,
                'page_3_numeric' => $page_3,
                'page_4_numeric' => $page_4,
            ];

            CachedResponses::updateOrCreate([
                'chart_id' => $this->chart->id,
                'domain' => $domain->domain
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
            ->select('searchanalytics', ['SUM(clicks) as count_clicks'])
            ->where('position >= ' . $lowPosition)
            ->where('position <= ' . $highPosition)
            ->where('date <= CURRENT_DATE()')
            ->where('domain = "' . $domain . '"')
            ->groupBy('date')
            ->get();
    }
}
