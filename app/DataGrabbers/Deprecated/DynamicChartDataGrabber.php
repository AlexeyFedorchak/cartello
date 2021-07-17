<?php

namespace App\DataGrabbers;

use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Sessions\Models\Session;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DynamicChartDataGrabber implements DataGrabber
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
        $this->chart->generateTimeRow();
    }

    /**
     * get rows
     *
     * @return array
     */
    public function rows(): array
    {
        $currentRow = $this->getRow(Session::currentYear());
        $prevRow = $this->getRow(Session::prevYear());

        $response = json_encode([
            'current' => $this->syncRowWithTime($currentRow),
            'previous' => $this->syncRowWithTime($prevRow),
            'overview' => $this->getOverview($currentRow, $prevRow),
        ]);

        CachedResponses::updateOrCreate(['chart_id' => $this->chart->id], ['response' => $response]);

        return json_decode($response, true);
    }

    /**
     * get row for specific time range for sessions
     *
     * @param Collection $sessions
     * @param array|null $columns
     * @return array
     */
    private function getRow(Collection $sessions, ?array $columns = null): array
    {
        $groupedData = [];

        foreach ($sessions as $session) {
            $groupNumber = Carbon::parse($session->date)->format('z');

            if ($this->chart->time_frame === ChartTimeFrames::WEEKLY) {
                $groupNumber = Carbon::parse($session->date)->format('W');
            }

            if ($this->chart->time_frame === ChartTimeFrames::MONTHLY) {
                $groupNumber = Carbon::parse($session->date)->format('m');
            }

            if (empty($groupedData[$groupNumber]))
                $groupedData[$groupNumber] = 0;

            $value = 0;

            if (!$columns)
                $columns = $this->chart->sourceColumns();

            foreach ($columns as $column)
                $value += $session->$column;

            $groupedData[$groupNumber] += $value;
        }

        return array_values($groupedData);
    }

    /**
     * sync row with time
     *
     * @param array $values
     * @return array
     */
    private function syncRowWithTime(array $values): array
    {
        $row = [];

        foreach ($this->chart->time_row as $key => $time)
            $row[$time] = $values[$key] ?? null;

        return $row;
    }

    /**
     * get overview
     *
     * @param array $currentRow
     * @param array $prevRow
     * @return array
     */
    private function getOverview(array $currentRow, array $prevRow): array
    {
        if (!$this->chart->has_overview)
            return [];

        $currentClicks = collect($currentRow)->last();
        $prevClicks = collect($prevRow)->last();

        $columns = $this->chart->sourceColumns();

        $hasBrandImpressions = false;
        $hasNonBrandImpressions = false;

        foreach ($columns as $column) {
            if (strpos($column, 'non_brand') !== false) {
                $hasNonBrandImpressions = true;
                continue;
            }

            if (strpos($column, 'brand') !== false)
                $hasBrandImpressions = true;
        }

        $currentImpressions = 0;
        $prevImpressions = 0;

        if ($hasBrandImpressions && $hasNonBrandImpressions) {
            $currentImpressions = collect($this->getRow(Session::currentYear(), ['total_impressions']))->last();
            $prevImpressions = collect($this->getRow(Session::prevYear(), ['total_impressions']))->last();
        }

        if ($hasBrandImpressions && !$hasNonBrandImpressions) {
            $currentImpressions = collect($this->getRow(Session::currentYear(), ['brand_impressions']))->last();
            $prevImpressions = collect($this->getRow(Session::prevYear(), ['brand_impressions']))->last();
        }

        if (!$hasBrandImpressions && $hasNonBrandImpressions) {
            $currentImpressions = collect($this->getRow(Session::currentYear(), ['non_brand_impressions']))->last();
            $prevImpressions = collect($this->getRow(Session::prevYear(), ['non_brand_impressions']))->last();
        }

        return [
            'clicks' => $currentClicks,
            'clicks_change' =>round((($currentClicks - $prevClicks) / $prevClicks) * 100, 2),
            'impressions' => $currentImpressions,
            'impressions_change' => round((($currentImpressions - $prevImpressions) / $prevImpressions) * 100, 2),
        ];
    }
}
