<?php

namespace App\Http\Controllers;

use App\Charts\Models\Chart;
use App\Http\Requests\ValidateGetChartDataRequest;

class GetChartDataAPIController extends Controller
{
    public function get(ValidateGetChartDataRequest $request)
    {
        return Chart::where($request->all())
            ->first()
            ->getData();
    }
}
