<?php

namespace App\Charts\Models;

use App\Charts\Constants\ChartPeriods;
use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Constants\ChartTypes;
use App\DataGrabbers\AVGPositionDynamicChartDataGrabber;
use App\DataGrabbers\ChangeTableDataGrabber;
use App\DataGrabbers\ChangeTableDataGrabberV2;
use App\DataGrabbers\DataGrabber;
use App\DataGrabbers\DynamicChartDataGrabber;
use App\DataGrabbers\DynamicChartDataGrabberV2;
use App\DataGrabbers\DynamicStructureDataGrabber;
use App\DataGrabbers\DynamicStructureDataGrabberPages;
use App\DataGrabbers\DynamicStructureDataGrabberV2;
use App\DataGrabbers\OpportunityTableDataGrabber;
use App\DataGrabbers\StructureDataCrabber;
use App\DataGrabbers\StructurePageDataGrabber;
use App\DataGrabbers\TableStructureChangeDataGrabber;
use App\DataGrabbers\TableStructureChangePageDataGrabber;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            return new DynamicChartDataGrabberV2($this);

        if ($this->type === ChartTypes::CHANGE_TABLE)
            return new ChangeTableDataGrabberV2($this);

        if ($this->type === ChartTypes::DYNAMIC_STRUCTURE)
            return new DynamicStructureDataGrabberV2($this);

        if ($this->type === ChartTypes::STRUCTURE)
            return new StructureDataCrabber($this);

        if ($this->type === ChartTypes::TABLE_STRUCTURE_CHANGE)
            return new TableStructureChangeDataGrabber($this);

        if ($this->type === ChartTypes::TABLE_STRUCTURE_CHANGE_PAGE)
            return new TableStructureChangePageDataGrabber($this);

        if ($this->type === ChartTypes::STRUCTURE_PAGE)
            return new StructurePageDataGrabber($this);

        if ($this->type === ChartTypes::DYNAMIC_STRUCTURE_PAGE)
            return new DynamicStructureDataGrabberPages($this);

        if ($this->type === ChartTypes::OPPORTUNITY_TABLE)
            return new OpportunityTableDataGrabber($this);

        if ($this->type === ChartTypes::AVG_POSITION)
            return new AVGPositionDynamicChartDataGrabber($this);

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

    /**
     * get related cached responses
     *
     * @return HasMany
     */
    public function getCachedResponses(): HasMany
    {
        return $this->hasMany(CachedResponses::class, 'chart_id', 'id');
    }
}
