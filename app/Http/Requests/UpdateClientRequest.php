<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    protected function prepareForValidation(): void
    {
        $sanitized = [];

        // Trim and sanitize string inputs
        foreach (['code', 'company_name', 'contact_name', 'email', 'phone', 'address', 'notes'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $sanitized[$field] = trim($this->input($field));
            }
        }

        // Ensure integer fields are integers
        if ($this->has('payment_terms_days')) {
            $sanitized['payment_terms_days'] = (int) $this->input('payment_terms_days');
        }

        if ($this->has('closing_day') && $this->input('closing_day') !== null) {
            $sanitized['closing_day'] = (int) $this->input('closing_day');
        }

        $this->merge($sanitized);
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id;

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('clients', 'code')->ignore($clientId)],
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'closing_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
