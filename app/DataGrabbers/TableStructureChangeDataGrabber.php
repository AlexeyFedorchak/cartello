<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Constants\ChartPeriods;
use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;

class TableStructureChangeDataGrabber implements DataGrabber
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
            $count_1_3_current = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain))
                ->pluck('count_clicks')
                ->sum();

            $count_4_7_current = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain, 4, 7))
                ->pluck('count_clicks')
                ->sum();

            $count_8_10_current = collect($this->getRow($currentStartDate, $currentEndDate, $domain->domain, 8, 10))
                ->pluck('count_clicks')
                ->sum();

            $count_1_3_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain))
                ->pluck('count_clicks')
                ->sum();

            $count_4_7_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain, 4, 7))
                ->pluck('count_clicks')
                ->sum();

            $count_8_10_prev = collect($this->getRow($prevStartDate, $prevEndDate, $domain->domain, 8, 10))
                ->pluck('count_clicks')
                ->sum();

            $rows[$domain->domain] = [
                'count_1_3' => [$count_1_3_current, $count_1_3_current - $count_1_3_prev],
                'count_4_7' => [$count_4_7_current, $count_4_7_current - $count_4_7_prev],
                'count_8_10' => [$count_8_10_current, $count_8_10_current - $count_8_10_prev],
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
