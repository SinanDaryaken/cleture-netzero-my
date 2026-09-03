<?php

namespace App\Http\Requests\Localization;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => [
                'required',
                'string',
                'size:2',
                Rule::exists('languages', 'code')->where(
                    fn ($query) => $query->where('active', true)->whereNull('deleted_at'),
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('locale'))) {
            $this->merge([
                'locale' => mb_strtolower(trim($this->string('locale')->toString())),
            ]);
        }
    }
}
