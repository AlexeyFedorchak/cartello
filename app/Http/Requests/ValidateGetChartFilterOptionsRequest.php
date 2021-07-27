<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateGetChartFilterOptionsRequest extends FormRequest
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
            'chart_id' => 'required|numeric|exists:filter_options,chart_id',
            'domains' => 'required|array',
            'domains.*' => 'required|string|max:255|exists:cashed_domain_list,domain',
        ];
    }
}
