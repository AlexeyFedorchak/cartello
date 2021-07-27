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
            'filters.clicks' => 'required|numeric',
            'filters.impressions' => 'required|numeric',
            'filters.opportunities' => 'required|numeric',
            'filters.position' => 'required|numeric',
            'domains' => 'required|array',
            'domains.*' => 'required|string|max:255|exists:cashed_domain_list,domain',
            'page' => 'required|numeric',
            'sort_by' => 'sometimes|nullable|string',
        ];
    }
    //http://localhost/api/opportunity-chart/filter?id=77&domains[]=https://ar-kuwait.namshi.com/&filters[clicks]=250&filters[impressions]=250000&filters[opportunities]=2500000&filters[position]=20&page=1
}
