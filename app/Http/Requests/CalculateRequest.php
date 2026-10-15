<?php

namespace App\Http\Requests;

use App\Services\LiquidityBalancer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CalculateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', LiquidityBalancer::MODE_REBALANCE),
            'coinA_adjusted' => $this->input('coinA_adjusted', 0),
            'coinB_adjusted' => $this->input('coinB_adjusted', 0),
            'slippage_pct' => $this->input('slippage_pct', 0),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $max = 'max:'.LiquidityBalancer::INPUT_MAX;

        return [
            'mode' => ['required', Rule::in(LiquidityBalancer::MODES)],
            'coinA_initial' => ['required', 'numeric', 'gt:0', $max],
            'coinB_initial' => ['required', 'numeric', 'gt:0', $max],
            'coinA_price' => ['required', 'numeric', 'gt:0', $max],
            'coinB_price' => ['required', 'numeric', 'gt:0', $max],
            'coinA_adjusted' => ['nullable', 'numeric', 'gte:0', $max],
            'coinB_adjusted' => ['nullable', 'numeric', 'gte:0', $max],
            'new_capital' => ['required_if:mode,deploy_budget', 'nullable', 'numeric', 'gt:0', $max],
            'slippage_pct' => ['nullable', 'numeric', 'min:0', 'max:'.LiquidityBalancer::SLIPPAGE_MAX_PCT],
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
            'new_capital.required_if' => 'New capital is required for Deploy budget mode.',
            'new_capital.gt' => 'New capital must be greater than zero.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $numericFields = [
                'coinA_initial',
                'coinB_initial',
                'coinA_price',
                'coinB_price',
                'coinA_adjusted',
                'coinB_adjusted',
                'new_capital',
            ];

            foreach ($numericFields as $field) {
                $raw = $this->input($field);
                if ($raw === null || $raw === '') {
                    continue;
                }

                if (! is_finite((float) $raw)) {
                    $validator->errors()->add($field, 'The '.$field.' must be a finite number.');
                }
            }

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
