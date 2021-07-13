<?php

namespace App\Http\Controllers;

use App\Charts\Constants\ChartTypes;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Exceptions\NoCacheFoundForGivenChart;
use App\Http\Requests\ValidateGetChartDataRequest;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Redis;

class GetChartDataAPIController extends Controller
{
    /**
     * get data for given chart
     *
     * @param ValidateGetChartDataRequest $request
     * @return array
     * @throws NoCacheFoundForGivenChart
     */
    public function get(ValidateGetChartDataRequest $request)
    {
//        $redisValue = Redis::get($this->getRedisUID($request));

        if (!empty($redisValue))
            return $redisValue;

        $cachedResponses = $this->getResponsesOrFail($request);
        $chart = $this->getChartWithTimeRow($request);

        if ($chart->type === ChartTypes::DYNAMIC_CHART) {
            $data = $this->getComputedDataForDynamicChart($cachedResponses);
        } elseif ($chart->type === ChartTypes::CHANGE_TABLE) {
            $data = $this->getComputedDataForChangeTable($cachedResponses);
        } elseif ($chart->type === ChartTypes::DYNAMIC_STRUCTURE || $chart->type === ChartTypes::DYNAMIC_STRUCTURE_PAGE) {
            $data = $this->getComputedDataForDynamicStructure($cachedResponses);
        } else {
            $data = $cachedResponses;
        }

        $response = [
            'row' => $data,
            'time_row' => $chart->time_row,
        ];

//        Redis::set($this->getRedisUID($request), json_encode($response), 'EX', 86400);

        return $response;
    }

    /**
     * get redis key
     *
     * @param FormRequest $request
     * @return string
     */
    private function getRedisUID(FormRequest $request): string
    {
        $redisUID = '';
        foreach ($request->all() as $key => $value)
            $redisUID .= $key . '-' . json_encode($value);

        return $redisUID;
    }

    /**
     * get cached responses
     *
     * @param FormRequest $request
     * @return mixed
     * @throws NoCacheFoundForGivenChart
     */
    private function getResponsesOrFail(FormRequest $request): array
    {
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
            ->pluck('response')
            ->toArray();

        if (count($cachedResponses) === 0)
            throw new NoCacheFoundForGivenChart();

        return $cachedResponses;
    }

    /**
     * get chart with generated time row
     *
     * @param FormRequest $request
     * @return mixed
     */
    private function getChartWithTimeRow(FormRequest $request)
    {
        $chart = Chart::where('id', $request->id)
            ->first();

        $chart->generateTimeRow();

        return $chart;
    }

    /**
     * get computed data
     *
     * @param array $cachedResponses
     * @return array[]
     */
    private function getComputedDataForDynamicChart(array $cachedResponses): array
    {
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

        return $data;
    }

    /**
     * get computed data for change table
     *
     * @param array $cachedResponses
     * @return array
     */
    private function getComputedDataForChangeTable(array $cachedResponses): array
    {
        $data = [];

        $months = [];
        $period = CarbonPeriod::create(now()->subMonths(6), now());

        foreach ($period as $item)
            $months[] = $item->format('M');

        $months = array_values(array_unique($months));

        for ($i = 0; $i < 7; $i++) {
            if ($i === 0)
                continue;

            foreach ($cachedResponses as $response) {
                if (empty($data[$i]['current_clicks']))
                    $data[$i]['current_clicks'] = 0;

                if (empty($data[$i]['prev_clicks']))
                    $data[$i]['prev_clicks'] = 0;

                if (empty($data[$i]['current_impressions']))
                    $data[$i]['current_impressions'] = 0;

                if (empty($data[$i]['prev_impressions']))
                    $data[$i]['prev_impressions'] = 0;

                $data[$i]['current_clicks'] += $response['current'][$i]['count_clicks'] ?? 0;
                $data[$i]['prev_clicks'] += $response['previous'][$i]['count_clicks'] ?? 0;

                $data[$i]['current_impressions'] += $response['current'][$i]['count_impressions'] ?? 0;
                $data[$i]['prev_impressions'] += $response['previous'][$i]['count_impressions'] ?? 0;
            }

            if (empty($data[$i]['prev_clicks']))
                $data[$i]['prev_clicks'] = 1;

            if (empty($data[$i]['prev_impressions']))
                $data[$i]['prev_impressions'] = 1;

            $data[$i]['change_clicks'] =
                round(($data[$i]['current_clicks'] - $data[$i]['prev_clicks']) / $data[$i]['prev_clicks'], 2) * 100;

            $data[$i]['change_impressions'] =
                round(($data[$i]['current_impressions'] - $data[$i]['prev_impressions']) / $data[$i]['prev_impressions'], 2) * 100;

            $data[$i]['month'] = $months[$i];
        }

        return array_values($data);
    }

    /**
     * get computed data for dynamic structure
     *
     * @param array $cachedResponses
     * @return array
     */
    public function getComputedDataForDynamicStructure(array $cachedResponses): array
    {
        $data = [];

        foreach ($cachedResponses as $response) {
            foreach ($response as $position => $days) {
                foreach ($days as $key => $counts) {
                    if (empty($data[$position][$key]))
                        $data[$position][$key] = 0;

                    $data[$position][$key] += $counts['count_clicks'];
                }
            }
        }

        return $data;
    }
}
