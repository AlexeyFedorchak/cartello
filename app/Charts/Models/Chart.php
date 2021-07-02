<?php

namespace App\Charts\Models;

use App\Charts\Constants\ChartPeriods;
use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Constants\ChartTypes;
use App\DataGrabbers\ChangeTableDataGrabber;
use App\DataGrabbers\DataGrabber;
use App\DataGrabbers\DynamicChartDataGrabber;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class Chart extends Model
{
    protected $table = 'charts';

    protected $fillable = [
        'slug',
        'name',
        'type',
        'time_frame',
        'period',
        'has_overview',
        'source_columns',
    ];

    /**
     * time row to keep time range
     *
     * @var array
     */
    public $time_row = [];

    /**
     * generate time row
     *
     * @return void
     */
    public function generateTimeRow(): void
    {
        $period = CarbonPeriod::create(now()->subYear(), now());

        if ($this->period === ChartPeriods::MONTH)
            $period = CarbonPeriod::create(now()->subMonth(), now());

        if ($this->period === ChartPeriods::WEEK)
            $period = CarbonPeriod::create(now()->subWeek(), now());

        $dates = [];
        foreach ($period as $date) {
            if ($this->time_frame === ChartTimeFrames::MONTHLY)
                $dates[] = $date->format('M Y');

            if ($this->time_frame === ChartTimeFrames::WEEKLY)
                $dates[] = $date->startOfWeek()->format('d M Y');

            if ($this->time_frame === ChartTimeFrames::DAILY)
                $dates[] = $date->format('d M Y');
        }

        $this->time_row = array_values(array_unique($dates));
    }

    /**
     * get source columns
     *
     * @return false|string[]
     */
    public function sourceColumns(): array
    {
        return explode('|', $this->source_columns);
    }

    /**
     * get grabber instance
     *
     * @return DynamicChartDataGrabber|null
     */
    public function getGrabber(): ?DataGrabber
    {
        if ($this->type === ChartTypes::DYNAMIC_CHART)
            return new DynamicChartDataGrabber($this);

        if ($this->type === ChartTypes::CHANGE_TABLE)
            return new ChangeTableDataGrabber($this);

        return null;
    }

    /**
     * get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->getGrabber()->rows();
    }
}
