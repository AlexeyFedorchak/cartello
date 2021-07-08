<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Constants\ChartPeriods;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;

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
        $currentEndDate = $this->switchDateString(now()->format('Y-m-d'));
        $currentStartDate = $this->switchDateString(now()->subMonth()->format('Y-m-d'));

        $prevEndDate = $currentStartDate;
        $prevStartDate = $this->switchDateString(now()->subMonths(2)->format('Y-m-d'));

        if ($this->chart->time_frame === ChartPeriods::YEAR) {
            $prevEndDate = $this->switchDateString(
                now()->subYear()->format('Y-m-d')
            );

            $prevStartDate = $this->switchDateString(
                now()->subYear()->subMonth()->format('Y-m-d')
            );
        }

        $rows = [];

        foreach (CachedDomainList::all() as $domain) {
            $page_1 = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain, 1, 10))
                ->pluck('count_clicks')
                ->sum();

            $page_2 = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain, 11, 20))
                ->pluck('count_clicks')
                ->sum();

            $page_3 = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain, 21, 30))
                ->pluck('count_clicks')
                ->sum();

            $page_4 = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain, 31, PHP_INT_MAX))
                ->pluck('count_clicks')
                ->sum();

            $page_1_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain, 1, 10))
                ->pluck('count_clicks')
                ->sum();

            $page_2_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain, 11, 20))
                ->pluck('count_clicks')
                ->sum();

            $page_3_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain, 21, 30))
                ->pluck('count_clicks')
                ->sum();

            $page_4_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain, 31, PHP_INT_MAX))
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
     * @param string $startDate
     * @param string $endDate
     * @param string $domain
     * @return mixed
     */
    private function getRow(string $startDate, string $endDate, string $domain, int $lowPosition = 1, int $highPosition = 3): array
    {
        return app(IClient::class)
            ->select('searchanalytics', ['SUM(clicks) as count_clicks'])
            ->where('position >= ' . $lowPosition)
            ->where('position <= ' . $highPosition)
            ->where('date <= DATE(' . $endDate . ')')
            ->where('date >= DATE(' . $startDate . ')')
            ->where('domain = "' . $domain . '"')
            ->groupBy('date')
            ->get();
    }
}
