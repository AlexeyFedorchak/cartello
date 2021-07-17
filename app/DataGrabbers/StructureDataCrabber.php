<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;

class StructureDataCrabber implements DataGrabber
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
            $count_1_3 = collect($this->getRow($domain->domain))
                ->pluck('count_clicks')
                ->sum();

            $count_4_7 = collect($this->getRow($domain->domain, 4, 7))
                ->pluck('count_clicks')
                ->sum();

            $count_8_10 = collect($this->getRow($domain->domain, 8, 10))
                ->pluck('count_clicks')
                ->sum();

            $total = $count_1_3 + $count_4_7 + $count_8_10;

            $rows[$domain->domain] = [
                'count_1_3' => round(($count_1_3 / $total) * 100, 1),
                'count_4_7' => round(($count_4_7 / $total) * 100, 1),
                'count_8_10' => round(($count_8_10 / $total) * 100, 1),
                'count_1_3_numeric' => $count_1_3,
                'count_4_7_numeric' => $count_4_7,
                'count_8_10_numeric' => $count_8_10
            ];

            CachedResponses::updateOrCreate([
                'chart_id' => $this->chart->id,
                'domain' => $domain->domain
            ], [
                'response' => json_encode($rows[$domain->domain]),
            ]);

            echo "Processed: " . $domain->domain . "\r\n";
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
