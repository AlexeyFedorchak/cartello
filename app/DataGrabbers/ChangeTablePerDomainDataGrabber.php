<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\BigQuery\Traits\BigQueryTimeFormat;
use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Models\CachedDomainList;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Sessions\Traits\BrandSessionsRegex;
use Carbon\Carbon;

class ChangeTablePerDomainDataGrabber implements DataGrabber
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
        $rows = [];

        foreach (CachedDomainList::all() as $domain) {
            $currentMonth = $this->getSessionsForPeriod(
                now()->subMonth(),
                now(),
                $this->chart->time_frame,
                $domain->domain
            );

            $prevMonth = $this->getSessionsForPeriod(
                now()->subYear()->subMonth(),
                now()->subYear(),
                $this->chart->time_frame,
                $domain->domain
            );

            if ($prevMonth['count_clicks'] === 0)
                $prevMonth['count_clicks'] = 1;

            $rows[$domain->domain] = [
                'domain' => $domain->domain,
                'clicks' => $currentMonth['count_clicks'],
                'change_clicks' =>
                    round(
                        ($currentMonth['count_clicks'] - $prevMonth['count_clicks']) / $prevMonth['count_clicks'],
                        4
                    ) * 100,
                'impressions' => $currentMonth['count_impressions'],
                'change_impressions' =>
                    round(
                        ($currentMonth['count_impressions'] - $prevMonth['count_impressions']) / $prevMonth['count_impressions'],
                        4
                    ) * 100,
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
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $period
     * @param string $domain
     * @return array
     */
    public function getSessionsForPeriod(Carbon $startDate, Carbon $endDate, string $period, string $domain): array
    {
        $startDate = $this->switchDateString($startDate->format('Y-m-d'));
        $endDate = $this->switchDateString($endDate->format('Y-m-d'));

        if ($period === ChartTimeFrames::MONTHLY)
            $period = 'month';

        if ($period === ChartTimeFrames::WEEKLY)
            $period = 'week';

        if ($period === ChartTimeFrames::DAILY)
            $period = 'day';

        $query = app(IClient::class)
            ->select('searchanalytics', [
                'SUM(clicks) as count_clicks',
                'SUM(impressions) as count_impressions',
                "DATE_TRUNC(DATE(date), " . $period . ") AS " . $period,
            ])
            ->where('date >= DATE(' . $startDate . ')')
            ->where('date <= DATE(' . $endDate . ')')
            ->where('domain = "' . $domain . '"');

        $query->groupBy($period)
            ->orderBy($period);

        return collect($query->get())->last();
    }
}
