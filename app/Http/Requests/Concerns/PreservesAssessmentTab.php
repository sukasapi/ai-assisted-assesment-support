<?php

namespace App\Http\Requests\Concerns;

use App\Support\AssessmentShowRedirect;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait PreservesAssessmentTab
{
    protected function failedValidation(Validator $validator): void
    {
        $tab = AssessmentShowRedirect::normalize($this->input('asesmen_tab'));
        $response = redirect()->back()->withInput()->withErrors($validator);

        if ($tab !== null && $tab !== 'overview') {
            $response->withFragment($tab);
        }

        throw new HttpResponseException($response);
    }
}
