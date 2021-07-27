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
//            'max_clicks' => 'required|numeric',
//            'max_impressions' => 'required|numeric',
//            'max_opportunities' => 'required|numeric',
//            'max_positions' => 'required|numeric',
//            'sort_by' => 'required|string',
        ];
    }
}
