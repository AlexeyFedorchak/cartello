<?php

namespace App\DataGrabbers;

use App\Charts\Models\Chart;

interface DataGrabber
{
    public function getRows(Chart $chart): array;
}
