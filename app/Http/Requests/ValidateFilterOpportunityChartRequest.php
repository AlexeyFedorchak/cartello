<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateFilterOpportunityChartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|numeric|exists:charts,id',
            'filters' => 'required|array',
            'filters.max_clicks' => 'required|numeric|min:0',
            'filters.max_impressions' => 'required|numeric|min:0',
            'filters.max_opportunities' => 'required|numeric|min:0',
            'filters.max_position' => 'required|numeric|min:1',
            'filters.min_clicks' => 'required|numeric|min:0',
            'filters.min_impressions' => 'required|numeric|min:0',
            'filters.min_opportunities' => 'required|numeric|min:0',
            'filters.min_position' => 'required|numeric|min:1',
            'domains' => 'required|array',
            'domains.*' => 'required|string|max:255|exists:cashed_domain_list,domain',
            'page' => 'required|numeric',
            'sort_by' => 'sometimes|nullable|string',
            'direction' => 'sometimes|nullable|string|min:3|max:4',
        ];
    }
    //http://localhost/api/opportunity-chart/filter?id=77&domains[]=https://ar-kuwait.namshi.com/&filters[max_clicks]=250&filters[max_impressions]=250000&filters[max_opportunities]=2500000&filters[max_position]=20&filters[min_clicks]=0&filters[min_impressions]=0&filters[min_opportunities]=0&filters[min_position]=1&page=1
}
