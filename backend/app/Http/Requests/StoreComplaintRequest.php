<?php

namespace App\Http\Requests;

use App\Support\Settings;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'booking_id' => ['nullable', 'integer', 'required_without:contract_id', 'prohibits:contract_id'],
            'contract_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in(Settings::get('complaint_types'))],
            'description' => ['required', 'string', 'max:2000'],
        ] + $this->attachmentRules();
    }
}
