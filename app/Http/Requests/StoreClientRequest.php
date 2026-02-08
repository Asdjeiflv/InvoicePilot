<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:clients,code'],
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
