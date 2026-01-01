<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrimeAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function validationData(): array
    {
        return array_merge($this->all(), [
            'crime_id' => $this->route('crime'),
        ]);
    }

    public function rules(): array
    {
        return [
            'crime_id' => ['required', 'integer', 'min:1', 'exists:crimes,id'],
        ];
    }
}
