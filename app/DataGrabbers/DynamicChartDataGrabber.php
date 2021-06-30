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
        return [
            $this->getRow(Session::prevYear(), $this->chart),
            $this->getRow(Session::currentYear(), $this->chart),
        ];
    }

    /**
     * get row for specific time range for sessions
     *
     * @param Collection $sessions
     * @param Chart $chart
     * @return array
     */
    private function getRow(Collection $sessions, Chart $chart): array
    {
        $groupedData = [];

        foreach ($sessions as $session) {
            $groupNumber = Carbon::parse($session->date)->format('z');

            if ($chart->time_frame === ChartTimeFrames::WEEKLY) {
                $groupNumber = Carbon::parse($session->date)->format('W');
            }

            if ($chart->time_frame === ChartTimeFrames::MONTHLY) {
                $groupNumber = Carbon::parse($session->date)->format('m');
            }

            if (empty($groupedData[$groupNumber])) {
                $groupedData[$groupNumber] = 0;
            }

            $value = 0;

            foreach ($chart->sourceColumns() as $column)
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
}
