<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class SourceConverterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBlocked() === false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'source_amount' => Money::normalizeUserInput($this->input('source_amount')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'source_amount' => 'bail|required|regex:/^\d+(\.\d{1,2})?$/|numeric|min:0.01',
            'source_currency_id' => 'bail|required|integer|exists:currencies,id',
            'target_currency_id' => 'bail|required|integer|exists:currencies,id',
        ];
    }
}
