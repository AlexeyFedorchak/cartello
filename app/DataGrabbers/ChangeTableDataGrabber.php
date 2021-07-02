<?php

namespace App\DataGrabbers;

use App\Charts\Models\Chart;
use App\Sessions\Models\Session;
use Carbon\CarbonPeriod;

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
        $currentRow = $this->getRow(Session::currentYear());
        $prevRow = $this->getRow(Session::prevYear());

        return [
            'non_brand_clicks' => [0, 0],
            'non_brand_clicks_change' => [0, 0],
            'non_brand_impressions' => [0, 0],
            'non_brand_impressions_change' => [0, 0],
        ];
    }

    /**
     * get months list
     *
     * @return array
     */
    public function getMonths(): array
    {
        $months = [];
        $period =  CarbonPeriod::create(now()->subMonths(6), now());

        foreach ($period as $month)
            $months[] = $month->format('F');

        return array_unique($months);
    }
}
