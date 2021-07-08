<?php

namespace App\Http\Controllers;

use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Exceptions\NoCacheFoundForGivenChart;
use App\Http\Requests\ValidateGetChartDataRequest;

class GetChartDataAPIController extends Controller
{
    public function get(ValidateGetChartDataRequest $request)
    {
        $cachedResponses = CachedResponses::where('chart_id', $request->id)
            ->where(function ($query) use ($request) {
                if (!empty($request->domain))
                    $query->whereIn('domain', $request->domain);
            })
            ->get();

        if ($cachedResponses->count() === 0)
            throw new NoCacheFoundForGivenChart();

        $chart = Chart::where('id', $request->id)
            ->first();

        $chart->generateTimeRow();

        return [
            'row' => $cachedResponses->map(function ($item) {
                $item->response = json_decode($item->response, true);

                return $item;
            }),
            'time_row' => $chart->time_row,
        ];
    }
}
