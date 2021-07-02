<?php

namespace App\DataGrabbers;

use App\Charts\Constants\ChartTimeFrames;
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

        return [
            'current' => $currentRow,
            'previous' => $this->getRow(Session::prevYear()),
            'overview' => $this->getOverview($currentRow),
        ];
    }

    /**
     * get row for specific time range for sessions
     *
     * @param Collection $sessions
     * @param Chart $chart
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

            if (empty($groupedData[$groupNumber])) {
                $groupedData[$groupNumber] = 0;
            }

            $value = 0;

            if (!$columns)
                $columns = $this->chart->sourceColumns();

            foreach ($columns as $column)
                $value += $session->$column;

            $groupedData[$groupNumber] += $value;
        }

        return $this->syncRowWithTime(array_values($groupedData));
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
     * @param $row
     * @return array
     */
    private function getOverview($row): array
    {
        if (!$this->chart->has_overview)
            return [];

        $clicks = collect($row)->last();
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

        $impressions = null;

        if ($hasBrandImpressions && $hasNonBrandImpressions)
            $impressions = collect($this->getRow(Session::currentYear(), ['total_impressions']))->last();

        if ($hasBrandImpressions && !$hasNonBrandImpressions)
            $impressions = collect($this->getRow(Session::currentYear(), ['brand_impressions']))->last();

        if (!$hasBrandImpressions && $hasNonBrandImpressions)
            $impressions = collect($this->getRow(Session::currentYear(), ['non_brand_impressions']))->last();

        return [
            'clicks' => $clicks,
            'clicks_change' => 0,
            'impressions' => $impressions,
            'impressions_change' => 0,
        ];
    }
}
