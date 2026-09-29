<?php

namespace App\Http\Requests;

use App\Enums\ChangeRequestType;
use App\Support\Settings;
use Illuminate\Validation\Rule;

class StoreChangeRequestRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ChangeRequestType::values())],
            'reason_type' => ['required', Rule::in(Settings::get('change_request_reasons'))],
            'details' => ['nullable', 'string', 'max:2000'],
            'requested_date' => ['required_if:type,terminate', 'nullable', 'date_format:Y-m-d'],
        ] + $this->attachmentRules();
    }
}
