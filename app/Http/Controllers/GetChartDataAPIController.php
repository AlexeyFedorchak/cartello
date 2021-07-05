<?php

namespace App\Http\Controllers;

use App\Charts\Models\CachedResponses;
use App\Http\Requests\ValidateGetChartDataRequest;

class GetChartDataAPIController extends Controller
{
    public function get(ValidateGetChartDataRequest $request)
    {
        $cache = CachedResponses::where('chart_id', $request->id)
            ->first();

        if (!$cache)
            throw new NoCacheFoundForGivenChart();

        return json_decode($cache->response, true);
    }
}
