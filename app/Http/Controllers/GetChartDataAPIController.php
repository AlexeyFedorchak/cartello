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
     * @return string
     * @throws NoCacheFoundForGivenChart
     */
    public function get(ValidateGetChartDataRequest $request): string
    {
//        $redisValue = Redis::get($this->getRedisUID($request));

        if (!empty($redisValue))
            return $redisValue;

        $cachedResponsesData = $this->getResponsesOrFail($request);

        $cachedResponses = $cachedResponsesData['responses'];
        $domains = $cachedResponsesData['domains'];

        $chart = $this->getChartWithTimeRow($request);

        if ($chart->type === ChartTypes::DYNAMIC_CHART) {
            $data = $this->getComputedDataForDynamicChart($cachedResponses);
        } elseif ($chart->type === ChartTypes::CHANGE_TABLE) {
            $data = $this->getComputedDataForChangeTable($cachedResponses);
        } elseif ($chart->type === ChartTypes::DYNAMIC_STRUCTURE || $chart->type === ChartTypes::DYNAMIC_STRUCTURE_PAGE) {
            $data = $this->getComputedDataForDynamicStructure($cachedResponses);
        } elseif ($chart->type === ChartTypes::OPPORTUNITY_TABLE) {
            $data = $this->getComputedDataForOpportunityTable($cachedResponses);
        } elseif ($chart->type === ChartTypes::AVG_POSITION) {
            $data = $this->getComputedDataForAvgPosition($cachedResponses);
        } elseif ($chart->type === ChartTypes::BRANDED_NON_BRANDED_CLICKS) {
            $data = $this->getComputedDataForBrandedNonBranded($cachedResponses);
        } elseif ($chart->type === ChartTypes::ORGANIC_CTR) {
            $data = $this->getComputedDataForCTR($cachedResponses);
        } elseif ($chart->type === ChartTypes::ORGANIC_CTR_TABLE_WEEKLY) {
            $data = $this->getComputedDataForCTRTable($cachedResponses, $domains);
        } else {
            $data = $cachedResponses;
        }

        $response = [
            'row' => $data,
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

    /**
     * group data for given set of domain
     *
     * @param array $cachedResponse
     * @return array
     */
    private function getComputedDataForOpportunityTable(array $cachedResponse): array
    {
        $data = [];

        foreach ($cachedResponse as $response) {
            $data = array_merge($data, $response);
        }

        return $data;
    }

    /**
     * get computed data for avg position chart
     *
     * @param array $cachedResponse
     * @return array
     */
    private function getComputedDataForAvgPosition(array $cachedResponse): array
    {
        $data = [];

        foreach ($cachedResponse as $response) {
            foreach ($response as $period => $days) {
                foreach ($days as $dayN => $dayV) {
                    if (empty($data[$period][$dayN]))
                        $data[$period][$dayN] = $dayV['avg_position'];

                    $data[$period][$dayN] = ($data[$period][$dayN] + $dayV['avg_position']) / 2;
                }
            }
        }

        return $data;
    }

    /**
     * get computed data for branded-non-branded dynamic charts
     *
     * @param array $cachedResponses
     * @return array
     */
    private function getComputedDataForBrandedNonBranded(array $cachedResponses): array
    {
        $data = [];

        foreach ($cachedResponses as $response)
            foreach ($response as $key => $days)
                foreach ($days as $dayN => $day) {
                    if (empty($data[$key][$dayN]))
                        $data[$key][$dayN] = 0;

                    $data[$key][$dayN] += $day['count_clicks'];
                }

        return $data;
    }

    private function getComputedDataForCTR(array $cachedResponses): array
    {
        $data = [];

        foreach ($cachedResponses as $response)
            foreach ($response as $key => $days)
                foreach ($days as $dayN => $ctr) {
                    if (empty($data[$key][$dayN]))
                        $data[$key][$dayN] = 0;

                    $data[$key][$dayN] = ($ctr + $data[$key][$dayN]) / 2;
                }

        return $data;
    }

    /**
     * reorder data for ctr table
     *
     * @param array $cachedResponses
     * @param $domains
     * @return array
     */
    private function getComputedDataForCTRTable(array $cachedResponses, $domains): array
    {
        $data = [];

        foreach ($cachedResponses as $domainN => $response) {
            foreach ($response['current'] as $weekN => $weekValue) {
                $data[] = [
                    'week' => $response['weekly_time_row'][$weekN],
                    'domain' => $domains[$domainN],
                    'ctr' => $weekValue,
                    'weekN' => $weekN,
                ];
            }
        }

        usort($data, function ($a, $b) {
           if ($a['weekN'] > $b['weekN']) return 1;
           if ($a['weekN'] < $b['weekN']) return -1;

           return 0;
        });

        return $data;
    }
}
