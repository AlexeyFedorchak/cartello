<?php

namespace App\Http\Controllers;

use App\Charts\Models\Chart;
use App\Http\Requests\ValidateGetChartsRequest;

class GetChartsAPIController extends Controller
{
    public function __invoke(ValidateGetChartsRequest $request)
    {
        return Chart::all();
    }
}
