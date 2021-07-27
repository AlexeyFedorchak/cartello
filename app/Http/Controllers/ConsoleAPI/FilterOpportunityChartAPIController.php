<?php

namespace App\Http\Controllers\ConsoleAPI;

use App\Charts\Models\Chart;
use App\Http\Requests\ValidateFilterOpportunityChartRequest;

class FilterOpportunityChartAPIController extends ConsoleAPIController
{
    public function filter(ValidateFilterOpportunityChartRequest $request)
    {
        $chart = Chart::where($request->only('id'))
            ->first();

        return $chart->getFilter()->filterAndSort(
            $request->page,
            $request->filters,
            $request->domains,
            !empty($request->sortBy) ? $request->sortBy : 'opportunities',
        );
    }
}
