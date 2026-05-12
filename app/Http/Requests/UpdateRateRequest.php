<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'rate' => ['required', 'numeric', 'gt:0', 'max:999999999.999999'],
            'variable_percentage' => ['required', 'numeric', 'min:0', 'max:10'],
            'fixed_fee' => ['required', 'numeric', 'min:0', 'max:999999.9999'],
        ];
    }
}
