<?php

namespace App\Http\Requests;

class RatingRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'service_score' => ['required', 'integer', 'between:1,5'],
            'worker_score' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            // للعقود: أي عاملة يُقيَّم (قد يعمل في العقد أكثر من عاملة)
            'worker_id' => [$this->routeIs('*.contracts.rate') ? 'required' : 'prohibited', 'integer'],
        ];
    }
}
