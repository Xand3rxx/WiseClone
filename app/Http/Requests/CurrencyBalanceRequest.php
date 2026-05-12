<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurrencyBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBlocked() === false;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'source_currency_id' => 'bail|required|integer|exists:currencies,id',
        ];
    }
}
