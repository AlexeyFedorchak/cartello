<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Constants\ChartPeriods;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use Carbon\Carbon;

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
        $rows = [];

        foreach (CachedDomainList::all() as $domain) {
            $count_1_3_current = collect($this->getRow(now()->subMonth(), now(), $domain->domain))
                ->pluck('count_clicks')
                ->sum();

            $count_4_7_current = collect($this->getRow(now()->subMonth(), now(), $domain->domain, 4, 7))
                ->pluck('count_clicks')
                ->sum();

            $count_8_10_current = collect($this->getRow(now()->subMonth(), now(), $domain->domain, 8, 10))
                ->pluck('count_clicks')
                ->sum();

            $startPrevDate = now()->subMonths(2);
            $endPrevDate = now()->subMonth();

            if ($this->chart->period === ChartPeriods::YEAR) {
                $startPrevDate = now()->subYear()->subMonth();
                $endPrevDate = now()->subYear();
            }

            $count_1_3_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain))
                ->pluck('count_clicks')
                ->sum();

            $count_4_7_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain, 4, 7))
                ->pluck('count_clicks')
                ->sum();

            $count_8_10_prev = collect($this->getRow($startPrevDate, $endPrevDate, $domain->domain, 8, 10))
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

            echo "Processed: " . $domain->domain . "\r\n";
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
