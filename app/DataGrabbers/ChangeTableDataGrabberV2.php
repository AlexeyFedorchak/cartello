<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Constants\ChartTable;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Sessions\Traits\BrandSessionsRegex;
use Carbon\Carbon;

class ChangeTableDataGrabberV2 implements DataGrabber
{
    use BrandSessionsRegex, BigQueryTimeFormat;
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
     * get rows related to change table chart
     *
     * @return array
     */
    public function rows(): array
    {
        $currentSessions = $this->getYearSessionsForPeriod(
            now()->startOfYear(),
            now()
        );

        $prevSessions = $this->getYearSessionsForPeriod(
            now()->subYear()->startOfYear(),
            now()->subYear()
        );

        $rows = [];

        foreach (CachedDomainList::all() as $domain) {
            $rows[$domain->domain] = [
                'current' => $currentSessions[$domain->domain],
                'previous' => $prevSessions[$domain->domain],
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
     * get sessions for given period
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    private function getYearSessionsForPeriod(Carbon $start, Carbon $end): array
    {
        $period = str_replace('monthly', 'month', $this->chart->time_frame);
        $period = str_replace('weekly', 'week', $period);
        $period = str_replace('daily', 'day', $period);
        $period = strtoupper($period);

        $currentYearSessions = [];

        if ($this->chart->source_columns === 'non_brand_clicks|non_brand_impressions')
            $currentYearSessions = $this->getSessions(
                $start,
                $end,
                $period,
                false
            );

        if ($this->chart->source_columns === 'brand_clicks|non_brand_clicks')
            $currentYearSessions = $this->getSessions(
                $start,
                $end,
                $period,
                false
            );

        if ($this->chart->source_columns === 'brand_clicks')
            $currentYearSessions = $this->getSessions(
                $start,
                $end,
                $period
            );

        if ($this->chart->source_columns === 'non_brand_clicks') {
            $currentYearBrandSessions = $this->getSessions(
                $start,
                $end,
                $period
            );

            $currentYearSessions = $this->getSessions(
                $start,
                $end,
                $period
            );

            $currentYearSessions = $this->subInts($currentYearSessions, $currentYearBrandSessions);
        }

        return $currentYearSessions;
    }

    /**
     * sub int values for two arrays
     *
     * @param array $list1
     * @param array $list2
     * @return array
     */
    private function subInts(array $list1, array $list2)
    {
        $list = [];

        foreach ($list1 as $x => $item) {
            foreach ($item as $key => $value) {
                if (!is_numeric($value)) {
                    $list[$x][$key] = $value;
                    continue;
                }

                $list[$x][$key] = $value - $list2[$x][$key];
            }
        }

        return $list;
    }

    /**
     * get session for given period
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $period
     * @param bool $isBrand
     * @return array
     */
    public function getSessions(Carbon $startDate, Carbon $endDate, string $period, bool $isBrand = true)
    {
        $startDate = $this->switchDateString($startDate->format('Y-m-d'));
        $endDate = $this->switchDateString($endDate->format('Y-m-d'));

        $data = [];
        foreach (CachedDomainList::all() as $domain) {
            $query = app(IClient::class)
                ->select(ChartTable::CHART_TABLE, [
                    'SUM(clicks) as count_clicks',
                    'SUM(impressions) as count_impressions',
                    "DATE_TRUNC(DATE(date), " . $period . ") AS " . $period,
                ])
                ->where('date >= DATE(' . $startDate . ')')
                ->where('date <= DATE(' . $endDate . ')')
                ->where('domain = "' . $domain->domain . '"');

            if ($isBrand) {
                $query->openGroupCondition();
                foreach ($this->brandKeywords() as $keyword)
                    $query->where('query like "%' . $keyword . '%"', 'OR');

                $query->closeGroupCondition();
            }

            $query->groupBy($period)
                ->orderBy($period);

            foreach ($query->get() as $item) {
                $data[$domain->domain][] = $item;
            }
        }

        return $data;
    }
}
