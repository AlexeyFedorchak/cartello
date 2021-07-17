<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Constants\ChartPeriods;
use App\Charts\Constants\ChartTable;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use Carbon\Carbon;

class TableStructureChangePageDataGrabber implements DataGrabber
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
            $page_1 = collect($this->getRow(now()->subMonth(), now(), $domain->domain, 1, 10))
                ->pluck('count_clicks')
                ->sum();

            $page_2 = collect($this->getRow(now()->subMonth(), now(), $domain->domain, 11, 20))
                ->pluck('count_clicks')
                ->sum();

            $page_3 = collect($this->getRow(now()->subMonth(), now(), $domain->domain, 21, 30))
                ->pluck('count_clicks')
                ->sum();

            $page_4 = collect($this->getRow(now()->subMonth(), now(), $domain->domain, 31, PHP_INT_MAX))
                ->pluck('count_clicks')
                ->sum();

            $startPrevDate = now()->subMonths(2);
            $endPrevDate = now()->subMonth();

            if ($this->chart->period === ChartPeriods::YEAR) {
                $startPrevDate = now()->subYear()->subMonth();
                $endPrevDate = now()->subYear();
            }

            $page_1_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain, 1, 10))
                ->pluck('count_clicks')
                ->sum();

            $page_2_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain, 11, 20))
                ->pluck('count_clicks')
                ->sum();

            $page_3_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain, 21, 30))
                ->pluck('count_clicks')
                ->sum();

            $page_4_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain, 31, PHP_INT_MAX))
                ->pluck('count_clicks')
                ->sum();

            $rows[$domain->domain] = [
                'page_1' => [$page_1, $page_1 - $page_1_prev],
                'page_2' => [$page_2, $page_2 - $page_2_prev],
                'page_3' => [$page_3, $page_3 - $page_3_prev],
                'page_4' => [$page_4, $page_4 - $page_4_prev],

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
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $domain
     * @return mixed
     */
    private function getRow(Carbon $startDate, Carbon $endDate, string $domain, int $lowPosition = 1, int $highPosition = 3): array
    {
        $startDate = $this->switchDateString($startDate->format('Y-m-d'));
        $endDate = $this->switchDateString($endDate->format('Y-m-d'));

        return app(IClient::class)
            ->select(ChartTable::CHART_TABLE, ['SUM(clicks) as count_clicks'])
            ->where('position >= ' . $lowPosition)
            ->where('position <= ' . $highPosition)
            ->where('date <= DATE(' . $endDate . ')')
            ->where('date >= DATE(' . $startDate . ')')
            ->where('domain = "' . $domain . '"')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
