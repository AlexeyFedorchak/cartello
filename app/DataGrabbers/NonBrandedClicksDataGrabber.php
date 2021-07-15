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

class NonBrandedClicksDataGrabber implements DataGrabber
{
    use BigQueryTimeFormat, BrandSessionsRegex;

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
            $currentNonBrandedClicks = $this->getClicks(
                now()->subYear(),
                now(),
                $domain->domain,
                $this->chart->time_frame
            );

            $prevNonBrandedClicks = $this->getClicks(
                now()->subYear(),
                now(),
                $domain->domain,
                $this->chart->time_frame
            );

            $rows[$domain->domain] = [
                'current' => $currentNonBrandedClicks,
                'prev' => $prevNonBrandedClicks,
            ];

            CachedResponses::updateOrCreate([
                'chart_id' => $this->chart->id,
                'domain' => $domain->domain,
            ], [
                'response' => json_encode($rows[$domain->domain])
            ]);

            echo "Processed: " . $domain->domain . "\r\n";
        }

        return $rows;
    }

    /**
     * get clicks
     *
     * @param Carbon $start
     * @param Carbon $end
     * @param string $domain
     * @param string $timeFrame
     * @param bool $isBranded
     * @return array
     */
    private function getClicks(Carbon $start, Carbon $end, string $domain, string $timeFrame): array
    {
        if ($timeFrame === ChartTimeFrames::DAILY)
            $timeFrame = 'day';

        if ($timeFrame === ChartTimeFrames::WEEKLY)
            $timeFrame = 'week';

        if ($timeFrame === ChartTimeFrames::MONTHLY)
            $timeFrame = 'month';

        $query = app(IClient::class)
            ->select('searchanalytics', [
                'SUM(clicks) as count_clicks',
                'SUM(impressions) as count_impressions',
                "DATE_TRUNC(DATE(date), " . $timeFrame . ") AS " . $timeFrame,
            ])
            ->where('date >= DATE(' . $this->switchDateString($start->format('Y-m-d')) . ')')
            ->where('date <= DATE(' . $this->switchDateString($end->format('Y-m-d')) . ')')
            ->where('domain = "' . $domain . '"');

        $query->openGroupCondition();

        foreach ($this->brandKeywords() as $keyword)
            $query->where('query not like "%' . $keyword . '%"', 'AND');

        $query->closeGroupCondition();

        return $query->groupBy($timeFrame)
            ->orderBy($timeFrame)
            ->get();
    }
}
