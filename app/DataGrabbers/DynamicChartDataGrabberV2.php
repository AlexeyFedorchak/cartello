<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Sessions\Traits\BrandSessionsRegex;
use Carbon\Carbon;

class DynamicChartDataGrabberV2 implements DataGrabber
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
     * get rows
     *
     * @return array
     */
    public function rows(): array
    {
        $currentYearSessions = $this->getYearSessionsForPeriod(now()->subYear(), now());
        $prevYearSessions = $this->getYearSessionsForPeriod(now()->subYears(2), now()->subYear());

//        $totalSessions = $this->overview(now()->subMonth(), now());
//        $totalSessionsPrev = $this->overview(now()->subMonths(2), now()->subMonth());

        $rows = [];
        foreach (CachedDomainList::all() as $domain) {
            $rows[$domain->domain] = [
                'current' => $currentYearSessions[$domain->domain],
                'previous' => $prevYearSessions[$domain->domain],
//                'overview' => [
//                    'count_clicks' => $totalSessions[$domain->domain]['count_clicks'],
//                    'count_clicks_change'
//                    => ($totalSessions[$domain->domain]['count_clicks'] - $totalSessionsPrev[$domain->domain]['count_clicks'])
//                        / $totalSessionsPrev[$domain->domain]['count_clicks'],
//                    'count_impressions' => $totalSessions[$domain->domain]['count_impressions'],
//                    'count_impressions_change'
//                    => ($totalSessions[$domain->domain]['count_impressions'] - $totalSessionsPrev[$domain->domain]['count_impressions'])
//                        / $totalSessionsPrev[$domain->domain]['count_impressions'],
//                ]
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
     * @return array
     */
    private function getYearSessionsForPeriod(Carbon $start, Carbon $end): array
    {
        $period = str_replace('monthly', 'month', $this->chart->time_frame);
        $period = str_replace('weekly', 'week', $period);
        $period = str_replace('daily', 'day', $period);
        $period = strtoupper($period);

        $currentYearSessions = [];

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

    public function getSessions(Carbon $startDate, Carbon $endDate, string $period, bool $isBrand = true)
    {
        $startDate = $this->switchDateString($startDate->format('Y-m-d'));
        $endDate = $this->switchDateString($endDate->format('Y-m-d'));

        $data = [];
        foreach (CachedDomainList::all() as $domain) {
            $query = app(IClient::class)
                ->select('searchanalytics', [
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

    private function overview(Carbon $startDate, Carbon $endDate)
    {
        $startDate = $this->switchDateString($startDate->format('Y-m-d'));
        $endDate = $this->switchDateString($endDate->format('Y-m-d'));

        $data = [];
        foreach (CachedDomainList::all() as $domain) {
            $query = app(IClient::class)
                ->select('searchanalytics', [
                    'SUM(clicks) as count_clicks',
                    'SUM(impressions) as count_impressions',
//                    "DATE_TRUNC(DATE(date), " . $period . ") AS " . $period,
                ])
                ->where('date >= DATE(' . $startDate . ')')
                ->where('date <= DATE(' . $endDate . ')')
                ->where('domain = "' . $domain->domain . '"');

            $query->openGroupCondition();
            foreach ($this->brandKeywords() as $keyword)
                $query->where('query like "%' . $keyword . '%"', 'OR');

            $query->closeGroupCondition();

            $query->groupBy('domain')
                ->orderBy('domain');

            foreach ($query->get() as $item) {
                $item['domain'] = $domain->domain;
                $data[$domain->domain] = $item;
            }
        }

        return $data;
    }
}
