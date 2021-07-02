<?php

namespace App\DataGrabbers;

use App\Charts\Models\Chart;
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
        return [

        ];
    }

    private function getRow(Collection $sessions, ?array $columns = null): array
    {

    }

}
