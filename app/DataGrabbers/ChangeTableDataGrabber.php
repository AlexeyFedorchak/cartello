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

    public function rows(): array
    {
        $currentRowClicks = $this->getRow(Session::months(6));
        $prevRowClicks = $this->getRow(Session::prevMonths(6));

        $currentRowImpressions = $this->getRow(Session::months(6), ['non_brand_impressions']);
        $prevRowImpressions = $this->getRow(Session::prevMonths(6), ['non_brand_impressions']);

        return [
            'non_brand_clicks' => $this->syncWithMonths($currentRowClicks),
            'non_brand_clicks_change' => $this->syncWithMonths($this->diff($currentRowClicks, $prevRowClicks)),
            'non_brand_impressions' => $this->syncWithMonths($currentRowImpressions),
            'non_brand_impressions_change' => $this->syncWithMonths($this->diff($currentRowImpressions, $prevRowImpressions)),
        ];
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
            $diff[] = (($digit - $prev[$key]) / $prev[$key]) * 100;

        return $diff;
    }

    /**
     * sync with months
     *
     * @param array $sessions
     * @return array
     */
    private function syncWithMonths(array $sessions): array
    {
        $sessionsToMonths = [];

        foreach ($this->getMonths() as $key => $month)
            $sessionsToMonths[$month] = $sessions[$key];

        return $sessionsToMonths;
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
