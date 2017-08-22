<?php

namespace App\Http\Requests\Api;

use App\Http\ApiRequest;

class RegisterOpen extends ApiRequest
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
            'uuid' => 'bail|required|string|unique:registers',
            'employee' => 'bail|required|string',
            'cashAmount' => 'bail|required|numeric|min:0',
            'openedAt' => 'sometimes|required|numeric|min:0|not_in:0',
        ];
    }
}
