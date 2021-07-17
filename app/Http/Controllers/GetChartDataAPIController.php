<?php

namespace App\Http\Controllers;

use App\Charts\Constants\ChartTypes;
use App\Charts\Models\CachedResponses;
use App\Charts\Models\Chart;
use App\Exceptions\NoCacheFoundForGivenChart;
use App\Http\Requests\ValidateGetChartDataRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Redis;

class GetChartDataAPIController extends Controller
{
    /**
     * get data for given chart
     *
     * @param ValidateGetChartDataRequest $request
     * @return string
     * @throws NoCacheFoundForGivenChart
     */
    public function get(ValidateGetChartDataRequest $request): string
    {
//        $redisValue = Redis::get($this->getRedisUID($request));

//        if (!empty($redisValue))
//            return $redisValue;

        $cachedResponsesData = $this->getResponsesOrFail($request);
        $chart = $this->getChartWithTimeRow($request);

        $response = [
            'row' => $chart->getComputedData($cachedResponsesData['responses'], $cachedResponsesData['domains']),
            'time_row' => $chart->time_row,
        ];

        if ($chart->type === ChartTypes::OPPORTUNITY_TABLE)
            $response = json_encode($response, JSON_UNESCAPED_UNICODE);
        else
            $response = json_encode($response);

//        Redis::set($this->getRedisUID($request), $response, 'EX', 86400);

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
            });

            $responses = $cachedResponses
                ->pluck('response')
                ->toArray();

            $domains = $cachedResponses
                ->pluck('domain')
                ->toArray();

        if (count($cachedResponses) === 0)
            throw new NoCacheFoundForGivenChart();

        return [
            'responses' => $responses,
            'domains' => $domains,
        ];
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
}
