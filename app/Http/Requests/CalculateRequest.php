<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'coinA_initial' => ['required', 'numeric', 'gt:0'],
            'coinB_initial' => ['required', 'numeric', 'gt:0'],
            'coinA_price' => ['required', 'numeric', 'gt:0'],
            'coinB_price' => ['required', 'numeric', 'gt:0'],
            'coinA_adjusted' => ['required', 'numeric', 'gte:0'],
            'coinB_adjusted' => ['required', 'numeric', 'gte:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coinA_initial.gt' => 'Coin A reference amount must be greater than zero.',
            'coinB_initial.gt' => 'Coin B equivalent amount must be greater than zero.',
            'coinA_price.gt' => 'Price of Coin A must be greater than zero.',
            'coinB_price.gt' => 'Price of Coin B must be greater than zero.',
            'coinA_adjusted.gte' => 'Your Coin A holdings cannot be negative.',
            'coinB_adjusted.gte' => 'Your Coin B holdings cannot be negative.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $initialValue = ((float) $this->input('coinA_initial') * (float) $this->input('coinA_price'))
                + ((float) $this->input('coinB_initial') * (float) $this->input('coinB_price'));

            if ($initialValue <= 0) {
                $validator->errors()->add(
                    'coinA_initial',
                    'Reference portfolio value must be greater than zero.'
                );
            }
        });
    }
}
