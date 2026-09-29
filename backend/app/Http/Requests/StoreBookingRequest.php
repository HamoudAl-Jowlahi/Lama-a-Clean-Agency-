<?php

namespace App\Http\Requests;

class StoreBookingRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'service_price_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'between:1,50'],
            'address_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
