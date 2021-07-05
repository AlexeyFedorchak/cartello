<?php

namespace App\DataGrabbers;

use App\BigQuery\IClient;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DynamicStructureDataGrabber implements DataGrabber
{
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
        $position_1_3 = $this->getRow();
        $position_4_7 = $this->getRow(4, 7);
        $position_8_10 = $this->getRow(8, 10);

        $response = json_encode([
            $this->syncWithTime($position_1_3),
            $this->syncWithTime($position_4_7),
            $this->syncWithTime($position_8_10),
        ]);

        CachedResponses::updateOrCreate(['chart_id' => $this->chart->id], ['response' => $response]);

        return json_decode($response, true);
    }

    /**
     * get row
     *
     * @param int $lowPosition
     * @param int $highPosition
     * @return mixed
     */
    private function getRow(int $lowPosition = 1, int $highPosition = 3): array
    {
        return app(IClient::class)
            ->select('searchanalytics', ['SUM(clicks) as count_clicks', 'SUM(impressions) as count_impressions', 'date'])
            ->where('position >= ' . $lowPosition)
            ->where('position <= ' . $highPosition)
            ->where('date <= CURRENT_DATE()')
            ->groupBy('date')
            ->get();
    }

    /**
     * sync with time
     *
     * @param array $rows
     * @return array
     */
    private function syncWithTime(array $rows): array
    {
        foreach ($rows as $key => $row) {
            $date = Carbon::parse($row['date'])->format('Y-m-d');

            $rows[$date] = $row;
        }

        return $rows;
    }
}
