<?php

namespace App\Http\Controllers;

use App\Charts\Models\FilterOption;
use App\Http\Requests\ValidateGetChartFilterOptionsRequest;

class GetChartFilterOptionsAPIController extends Controller
{
    /**
     * get filter options
     *
     * @param ValidateGetChartFilterOptionsRequest $request
     * @return array
     */
    public function get(ValidateGetChartFilterOptionsRequest $request): array
    {
        $domains = explode(',', str_replace(['[', ']', '"', '"'], '', ($request->domains[0])));

        $filterOptions = FilterOption::where($request->only('chart_id'))
            ->where(function ($query) use ($domains) {
                if (is_array($domains) && count($domains) > 0)
                    $query->whereIn('domain', $domains);
            })
            ->get();

        return $this->computeOptions($filterOptions->pluck('options')->toArray());
    }

    /**
     * compute options
     *
     * @param array $options
     * @return array
     */
    private function computeOptions(array $options): array
    {
        $options = array_map(function ($option) {
            return json_decode($option, true);
        }, $options);

        $computedOptions = [];

        foreach ($options as $option) {
            foreach ($option as $y => $value) {
                if (!isset($computedOptions[$y]))
                    $computedOptions[$y] = 0;

                if (strpos($y, 'max') !== false) {
                    $computedOptions[$y] = max($value, $computedOptions[$y]);
                    continue;
                }

                if ($y === 'per_page') {
                    $computedOptions[$y] = $value;
                    continue;
                }

                $computedOptions[$y] += $value;
            }
        }

        return $computedOptions;
    }
}
