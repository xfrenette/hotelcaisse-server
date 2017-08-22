<?php

namespace App\Http;

use Illuminate\Foundation\Http\FormRequest;

class ApiRequest extends FormRequest
{
    /**
     * Override the method to return the data in the JSON's `data` attribute
     *
     * @return \Illuminate\Support\Collection
     */
    protected function validationData()
    {
        return $this->json('data');
    }
}
