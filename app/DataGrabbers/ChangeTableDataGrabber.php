<?php

namespace App\DataGrabbers;

use App\Charts\Models\Chart;
use App\Sessions\Models\Session;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;

class ChangeTableDataGrabber implements DataGrabber
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
     * get rows related to change table chart
     *
     * @return array
     */
    public function rows(): array
    {
        $currentRowClicks = $this->getRow(Session::months(6));
        $prevRowClicks = $this->getRow(Session::prevMonths(6));

        $currentRowImpressions = $this->getRow(Session::months(6), ['non_brand_impressions']);
        $prevRowImpressions = $this->getRow(Session::prevMonths(6), ['non_brand_impressions']);

        return $this->syncWithMonths(
            [
                $currentRowClicks,
                $this->diff($currentRowClicks, $prevRowClicks),
                $currentRowImpressions,
                $this->diff($currentRowImpressions, $prevRowImpressions)
            ],
            [
                'non_brand_clicks',
                'non_brand_clicks_change',
                'non_brand_impressions',
                'non_brand_impressions_change'
            ]
        );
    }

    /**
     * get row for specific collection
     *
     * @param Collection $sessions
     * @param array|null $columns
     * @return array
     */
    private function getRow(Collection $sessions, ?array $columns = null): array
    {
        $groupedData = [];

        foreach ($sessions as $session) {
            $groupNumber = Carbon::parse($session->date)->format('F');

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
     * get diff between two arrays
     *
     * @param array $current
     * @param array $prev
     * @return array
     */
    private function diff(array $current, array $prev): array
    {
        $diff = [];

        foreach ($current as $key => $digit)
            $diff[] = round((($digit - $prev[$key]) / $prev[$key]) * 100, 2);

        return $diff;
    }

    /**
     * sync with months
     *
     * @param array $data
     * @param array $mapKeys
     * @return array
     */
    private function syncWithMonths(array $data, array $mapKeys = []): array
    {
        $trans = [];

        for ($i = 0; $i < count($data[0]); $i++)
            foreach ($data as $key => $array)
                $trans[$i][$mapKeys[$key]] = $array[$i];

        foreach ($this->getMonths() as $key => $month)
            $trans[$key]['month'] = $month;

        return $trans;
    }

    /**
     * get months list
     *
     * @return array
     */
    public function getMonths(): array
    {
        $months = [];
        $period =  CarbonPeriod::create(now()->subMonths(5), now());

        foreach ($period as $month)
            $months[] = $month->format('F');

        return array_values(array_unique($months));
    }
}
