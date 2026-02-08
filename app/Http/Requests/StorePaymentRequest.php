<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    protected function prepareForValidation(): void
    {
        $sanitized = [];

        // Sanitize string fields
        foreach (['method', 'reference_no', 'note'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $sanitized[$field] = trim($this->input($field));
            }
        }

        // Ensure numeric/integer fields
        if ($this->has('invoice_id')) {
            $sanitized['invoice_id'] = (int) $this->input('invoice_id');
        }

        if ($this->has('amount')) {
            $sanitized['amount'] = (float) $this->input('amount');
        }

        $this->merge($sanitized);
    }

    public function rules(): array
    {
        return [
            'invoice_id' => [
                'required',
                'exists:invoices,id',
            ],
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $invoice = Invoice::find($this->invoice_id);

                    if (!$invoice) {
                        return; // Will be caught by invoice_id validation
                    }

                    // Check if invoice can receive payment
                    if (!$invoice->canReceivePayment()) {
                        $fail('この請求書は入金を受け付けられません（ステータス: ' . $invoice->status . '）');
                        return;
                    }

                    // Check if amount exceeds balance
                    if ($value > $invoice->balance_due) {
                        $fail(sprintf(
                            '入金額（¥%s）が残高（¥%s）を超えています',
                            number_format($value, 2),
                            number_format($invoice->balance_due, 2)
                        ));
                    }
                },
            ],
            'method' => [
                'required',
                'string',
                'in:bank_transfer,cash,credit_card,check,other',
            ],
            'reference_no' => [
                'nullable',
                'string',
                'max:255',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required' => '請求書を選択してください',
            'invoice_id.exists' => '指定された請求書が存在しません',
            'payment_date.required' => '入金日を入力してください',
            'payment_date.date' => '有効な日付を入力してください',
            'payment_date.before_or_equal' => '入金日は今日以前の日付を指定してください',
            'amount.required' => '入金額を入力してください',
            'amount.numeric' => '入金額は数値で入力してください',
            'amount.min' => '入金額は0.01以上を指定してください',
            'method.required' => '支払方法を選択してください',
            'method.in' => '有効な支払方法を選択してください',
        ];
    }
}
