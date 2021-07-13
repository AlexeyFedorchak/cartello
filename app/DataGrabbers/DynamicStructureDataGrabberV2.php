<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;

class DynamicStructureDataGrabberV2 implements DataGrabber
{
    use BigQueryTimeFormat;

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
            $position_1_3 = $this->getRow($domain->domain);
            $position_4_7 = $this->getRow($domain->domain, 4, 7,);
            $position_8_10 = $this->getRow($domain->domain, 8, 10,);

            $rows[$domain->domain] = [
                'position_1_3' => $position_1_3,
                'position_4_7' => $position_4_7,
                'position_8_10' => $position_8_10,
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
            ->where('date >= DATE(' . $this->switchDateString(now()->subYear()->format('Y-m-d')) . ')')
            ->where('domain = "' . $domain . '"')
            ->groupBy('date')
            ->get();
    }
}
