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
            $redisUID .= $key . '-' . json_encode($value);

        $redisValue = Redis::get($redisUID);

        if (!empty($redisValue))
            return $redisValue;

        $cachedResponses = CachedResponses::where('chart_id', $request->id)
            ->where(function ($query) use ($request) {
                if (!empty($request->filter))
                    $query->whereIn('domain', $request->filter);
            })
            ->get()
            ->map(function ($item) {
                $item->response = json_decode($item->response, true);

                return $item;
            })
            ->pluck('response');

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

            $currentCountImpressions = 0;
            $prevCountImpressions = 0;

            foreach ($cachedResponses as $cache) {
                foreach ($cache['current'] as $dayN => $current) {
                    if (empty($data['current'][$dayN]))
                        $data['current'][$dayN] = 0;

                    $data['current'][$dayN] += $current['count_clicks'];

                    $currentCountImpressions += $current['count_impressions'];
                }

                foreach ($cache['previous'] as $dayN => $previous) {
                    if (empty($data['previous'][$dayN]))
                        $data['previous'][$dayN] = 0;

                    $data['previous'][$dayN] += $previous['count_clicks'];

                    $prevCountImpressions += $previous['count_impressions'];
                }
            }

            $totalClicksCurrent = array_sum($data['current']);
            $totalClicksPrev = array_sum($data['previous']);

            $data['overview'] = [
                'current_count_clicks' => $totalClicksCurrent,
                'prev_count_clicks' => $totalClicksPrev,
                'change_clicks' => round((($totalClicksCurrent - $totalClicksPrev) / $totalClicksPrev) * 100, 2),

                'current_count_impressions' => $currentCountImpressions,
                'prev_count_impressions' => $prevCountImpressions,
                'change_impressions' => round((($currentCountImpressions - $prevCountImpressions) / $prevCountImpressions) * 100, 2),
            ];
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
