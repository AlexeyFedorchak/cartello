<?php

namespace App\Http\Controllers;

use App\Charts\Constants\ChartTypes;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Exceptions\NoCacheFoundForGivenChart;
use App\Http\Requests\ValidateGetChartDataRequest;
use Illuminate\Support\Facades\Redis;

class GetChartDataAPIController extends Controller
{
    public function get(ValidateGetChartDataRequest $request)
    {
        $redisUID = '';
        foreach ($request->all() as $key => $value)
            $redisUID .= $key . '-' . $value;

        $redisValue = Redis::get($redisUID);

        if (!empty($redisValue))
            return $redisValue;

        $cachedResponses = CachedResponses::where('chart_id', $request->id)
            ->where(function ($query) use ($request) {
                if (!empty($request->domain))
                    $query->whereIn('domain', $request->domain);
            })
            ->get()
            ->map(function ($item) {
                $item->response = json_decode($item->response, true);

                return $item;
            })
            ->pluck('response')
            ->toArray();

        if (count($cachedResponses) === 0)
            throw new NoCacheFoundForGivenChart();

        $chart = Chart::where('id', $request->id)
            ->first();

        $chart->generateTimeRow();

        if ($chart->type === ChartTypes::DYNAMIC_CHART) {
            $data = [
                'current' => [],
                'previous' => [],
            ];

            foreach ($cachedResponses as $cache) {
                foreach ($cache['current'] as $dayN => $current) {
                    if (empty($data['current'][$dayN]))
                        $data['current'][$dayN] = 0;

                    $data['current'][$dayN] += $current['count_clicks'];
                }

                foreach ($cache['previous'] as $dayN => $previous) {
                    if (empty($data['previous'][$dayN]))
                        $data['previous'][$dayN] = 0;

                    $data['previous'][$dayN] += $previous['count_clicks'];
                }
            }
        } else {
            $data = $cachedResponses;
        }

        $response = [
            'row' => $data,
            'time_row' => $chart->time_row,
        ];

        Redis::set($redisUID, json_encode($response), 'EX', 86400);

        return $response;
    }
}
