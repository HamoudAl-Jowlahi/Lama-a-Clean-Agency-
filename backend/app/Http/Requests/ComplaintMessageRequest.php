<?php

namespace App\Http\Requests;

class ComplaintMessageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ] + $this->attachmentRules();
    }
}
