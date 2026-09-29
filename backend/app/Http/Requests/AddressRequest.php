<?php

namespace App\Http\Requests;

class AddressRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:60'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', 'max:120'],
            'street' => ['nullable', 'string', 'max:160'],
            'building' => ['nullable', 'string', 'max:60'],
            'floor' => ['nullable', 'string', 'max:20'],
            'details' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'make_default' => ['nullable', 'boolean'],
        ];
    }
}
