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

    /**
     * get year session for given period
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

        if ($this->chart->source_columns === 'brand_clicks|non_brand_clicks')
            $currentYearSessions = $this->getSessions(
                $start,
                $end,
                $period
            );

        if ($this->chart->source_columns === 'brand_clicks')
            $currentYearSessions = $this->getSessions(
                $start,
                $end,
                $period,
                true
            );

        if ($this->chart->source_columns === 'non_brand_clicks') {
            $currentYearSessionsALL = $this->getSessions(
                $start,
                $end,
                $period
            );

            $currentYearSessionsBranded = $this->getSessions(
                $start,
                $end,
                $period,
                true
            );

            $currentYearSessions = [];

            foreach ($currentYearSessionsALL as $domain => $session) {
                foreach ($session as $i => $node) {
                    foreach ($node as $j => $value) {
                        if (!is_numeric($value)) {
                            $currentYearSessions[$domain][$i][$j] = $value;
                            continue;
                        }

                        if (!isset($currentYearSessionsBranded[$domain][$i][$j]))
                            $currentYearSessionsBranded[$domain][$i][$j] = 0;

                        $currentYearSessions[$domain][$i][$j] =
                            $value - $currentYearSessionsBranded[$domain][$i][$j];
                    }
                }
            }
        }

        return $currentYearSessions;
    }

    /**
     * get session data
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $period
     * @param int $useBrand
     * @return array
     */
    private function getSessions(Carbon $startDate, Carbon $endDate, string $period, bool $isBrand = false): array
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

    /**
     * get overview
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function overview(Carbon $startDate, Carbon $endDate): array
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
