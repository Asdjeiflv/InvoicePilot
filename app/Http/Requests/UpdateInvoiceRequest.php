<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    protected function prepareForValidation(): void
    {
        $sanitized = [];

        // Sanitize string fields
        if ($this->has('notes') && is_string($this->input('notes'))) {
            $sanitized['notes'] = trim($this->input('notes'));
        }

        // Ensure proper types for IDs
        if ($this->has('client_id')) {
            $sanitized['client_id'] = (int) $this->input('client_id');
        }

        // Sanitize items array
        if ($this->has('items') && is_array($this->input('items'))) {
            $sanitizedItems = [];
            foreach ($this->input('items') as $item) {
                if (is_array($item)) {
                    $sanitizedItems[] = [
                        'description' => isset($item['description']) && is_string($item['description'])
                            ? trim($item['description'])
                            : ($item['description'] ?? ''),
                        'quantity' => isset($item['quantity']) ? (float) $item['quantity'] : 0,
                        'unit_price' => isset($item['unit_price']) ? (float) $item['unit_price'] : 0,
                        'tax_rate' => isset($item['tax_rate']) ? (float) $item['tax_rate'] : 0,
                    ];
                }
            }
            $sanitized['items'] = $sanitizedItems;
        }

        $this->merge($sanitized);
    }

    public function rules(): array
    {
        $invoice = $this->route('invoice');

        $rules = [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],
            'issue_date' => [
                'required',
                'date',
            ],
            'due_date' => [
                'required',
                'date',
                'after_or_equal:issue_date',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.description' => [
                'required',
                'string',
                'max:1000',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'items.*.tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'version' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];

        // Prevent editing of paid or canceled invoices
        if ($invoice && in_array($invoice->status, ['paid', 'canceled'])) {
            $rules['client_id'][] = function ($attribute, $value, $fail) {
                $fail('支払済みまたはキャンセル済みの請求書は編集できません');
            };
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'client_id.required' => '取引先を選択してください',
            'client_id.exists' => '指定された取引先が存在しません',
            'issue_date.required' => '発行日を入力してください',
            'issue_date.date' => '有効な日付を入力してください',
            'due_date.required' => '支払期限を入力してください',
            'due_date.date' => '有効な日付を入力してください',
            'due_date.after_or_equal' => '支払期限は発行日以降の日付を指定してください',
            'items.required' => '明細行を追加してください',
            'items.min' => '少なくとも1つの明細行が必要です',
            'items.*.description.required' => '商品・サービスの説明を入力してください',
            'items.*.description.max' => '説明は1000文字以内で入力してください',
            'items.*.quantity.required' => '数量を入力してください',
            'items.*.quantity.min' => '数量は0.01以上を指定してください',
            'items.*.unit_price.required' => '単価を入力してください',
            'items.*.unit_price.min' => '単価は0以上を指定してください',
            'items.*.tax_rate.required' => '税率を入力してください',
            'items.*.tax_rate.min' => '税率は0以上を指定してください',
            'items.*.tax_rate.max' => '税率は100以下を指定してください',
            'status.prohibited' => '支払済みまたはキャンセル済みの請求書は編集できません',
        ];
    }
}
