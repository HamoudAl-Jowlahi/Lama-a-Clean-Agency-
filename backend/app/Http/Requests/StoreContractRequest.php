<?php

namespace App\Http\Requests;

class StoreContractRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer'],
            'address_id' => ['required', 'integer'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'months' => ['required', 'integer', 'between:1,24'],
            'accept_terms' => ['accepted'],
            'terms_version' => ['required', 'string', 'max:20'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
