<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBlocked() === false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_amount' => $this->sanitizeNumericInput($this->input('source_amount')),
            'target_amount' => $this->sanitizeNumericInput($this->input('target_amount')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'recipient_uuid' => [
                'bail',
                'required',
                'string',
                Rule::exists('users', 'uuid')
                    ->whereNull('blocked_at')
                    ->whereNull('deleted_at'),
            ],
            'source_amount' => 'bail|required|regex:/^\d+(\.\d{1,2})?$/|numeric|min:0.01|max:99999999.99',
            'target_amount' => 'bail|required|regex:/^\d+(\.\d{1,2})?$/|numeric|min:0|max:99999999.99',
            'quote_uuid' => 'bail|required|string|exists:transfer_quotes,uuid',
            'idempotency_key' => 'bail|required|string|max:100',
            'source_currency_id' => 'bail|required|integer|exists:currencies,id',
            'target_currency_id' => 'bail|required|integer|exists:currencies,id',
        ];
    }

    private function sanitizeNumericInput(mixed $value): mixed
    {
        return Money::normalizeUserInput($value);
    }
}
