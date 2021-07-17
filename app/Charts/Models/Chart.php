<?php

namespace App\Charts\Models;

use App\Charts\Constants\ChartPeriods;
use App\Charts\Constants\ChartTimeFrames;
use App\Charts\Constants\ChartTypes;
use App\DataComputers\AVGPositionDataComputer;
use App\DataComputers\BrandedNonBrandedClicksDataComputer;
use App\DataComputers\ChangeTableDataComputer;
use App\DataComputers\CTRChartDataComputer;
use App\DataComputers\CTRTableChartDataComputer;
use App\DataComputers\DataComputer;
use App\DataComputers\DefaultDataComputer;
use App\DataComputers\DynamicChartDataComputer;
use App\DataComputers\DynamicStructureDataComputer;
use App\DataComputers\NonBrandedClicksDataComputer;
use App\DataComputers\NonBrandedClicksPerDeviceDataComputer;
use App\DataComputers\OpportunityTableDataComputer;
use App\DataGrabbers\AVGPositionDynamicChartDataGrabber;
use App\DataGrabbers\BrandedNonBrandedClicksChartDataGrabber;
use App\DataGrabbers\ChangeTableDataGrabber;
use App\DataGrabbers\ChangeTableDataGrabberV2;
use App\DataGrabbers\ChangeTablePerDomainDataGrabber;
use App\DataGrabbers\DataGrabber;
use App\DataGrabbers\DynamicChartDataGrabber;
use App\DataGrabbers\DynamicChartDataGrabberV2;
use App\DataGrabbers\DynamicStructureDataGrabber;
use App\DataGrabbers\DynamicStructureDataGrabberPages;
use App\DataGrabbers\DynamicStructureDataGrabberV2;
use App\DataGrabbers\NonBrandedClicksDataGrabber;
use App\DataGrabbers\NonBrandedClicksPerDeviceChartDataGrabber;
use App\DataGrabbers\OpportunityTableDataGrabber;
use App\DataGrabbers\OrganicCTRChartDataGrabber;
use App\DataGrabbers\OrganicCTRTableDataGrabber;
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
        $period = CarbonPeriod::create(now()->startOfYear(), now());

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

        if ($this->type === ChartTypes::BRANDED_NON_BRANDED_CLICKS)
            return new BrandedNonBrandedClicksChartDataGrabber($this);

        if ($this->type === ChartTypes::ORGANIC_CTR)
            return new OrganicCTRChartDataGrabber($this);

        if ($this->type === ChartTypes::ORGANIC_CTR_TABLE_WEEKLY)
            return new OrganicCTRTableDataGrabber($this);

        if ($this->type === ChartTypes::CHANGE_TABLE_PER_DOMAINS)
            return new ChangeTablePerDomainDataGrabber($this);

        if ($this->type === ChartTypes::NON_BRANDED_CLICKS)
            return new NonBrandedClicksDataGrabber($this);

        if ($this->type === ChartTypes::NON_BRANDED_CLICKS_PER_DEVICE)
            return new NonBrandedClicksPerDeviceChartDataGrabber($this);

        return null;
    }

    /**
     * get computer for processing cached data
     *
     * @return DataComputer|null
     */
    public function getComputer(): ?DataComputer
    {
        if ($this->type === ChartTypes::DYNAMIC_CHART)
            return new DynamicChartDataComputer();

        if ($this->type === ChartTypes::CHANGE_TABLE)
            return new ChangeTableDataComputer();

        if ($this->type === ChartTypes::DYNAMIC_STRUCTURE || $this->type === ChartTypes::DYNAMIC_STRUCTURE_PAGE)
            return new DynamicStructureDataComputer();

        if ($this->type === ChartTypes::OPPORTUNITY_TABLE)
            return new OpportunityTableDataComputer();

        if ($this->type === ChartTypes::AVG_POSITION)
            return new AVGPositionDataComputer();

        if ($this->type === ChartTypes::BRANDED_NON_BRANDED_CLICKS)
            return new BrandedNonBrandedClicksDataComputer();

        if ($this->type === ChartTypes::ORGANIC_CTR)
            return new CTRChartDataComputer();

        if ($this->type === ChartTypes::ORGANIC_CTR_TABLE_WEEKLY)
            return new CTRTableChartDataComputer();

        if ($this->type === ChartTypes::NON_BRANDED_CLICKS)
            return new NonBrandedClicksDataComputer();

        if ($this->type === ChartTypes::NON_BRANDED_CLICKS_PER_DEVICE)
            return new NonBrandedClicksPerDeviceDataComputer();

        return new DefaultDataComputer();
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
     * get computed data
     *
     * @param array $cachedResponses
     * @param array $domains
     * @return array
     */
    public function getComputedData(array $cachedResponses, array $domains): array
    {
        return $this->getComputer()->compute($cachedResponses, $domains);
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
