<?php

namespace App\Http\Controllers;

use App\Charts\Models\Chart;
use App\Http\Requests\ValidateGetChartDataRequest;

class GetChartDataAPIController extends Controller
{
    public function __invoke(ValidateGetChartDataRequest $request)
    {
        return Chart::where($request->all())->getData();
    }
}
