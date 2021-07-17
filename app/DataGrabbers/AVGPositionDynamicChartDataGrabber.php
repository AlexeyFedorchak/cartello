<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use Carbon\Carbon;

class AVGPositionDynamicChartDataGrabber implements DataGrabber
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
     * get rows related to avg position chart
     *
     * @return array
     */
    public function rows(): array
    {
        $rows = [];

        foreach (CachedDomainList::all() as $domain) {
            $current = $this->getAvgPositionForPeriod(now()->subYear(), now(), $domain->domain);
            $prev = $this->getAvgPositionForPeriod(now()->subYears(2), now()->subYear(), $domain->domain);

            $rows[$domain->domain] = [
                'current' => $current,
                'prev' => $prev,
            ];

            CachedResponses::updateOrCreate([
                'chart_id' => $this->chart->id,
                'domain' => $domain->domain,
            ], [
                'response' => json_encode($rows[$domain->domain])
            ]);
        }

        return $rows;
    }

    /**
     * get avg position for period based on given domain
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param string $domain
     * @return mixed
     */
    private function getAvgPositionForPeriod(Carbon $start, Carbon $end, string $domain)
    {
        return app(IClient::class)
            ->select('searchanalytics', ['AVG(position) as avg_position'])
            ->where('date >= DATE(' . $this->switchDateString($start->format('Y-m-d')) . ')')
            ->where('date <= DATE(' . $this->switchDateString($end->format('Y-m-d')) . ')')
            ->where('domain = "' . $domain . '"')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
