<?php

namespace App\Http\Requests\Organizations;

use App\Models\OrganizationUser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof OrganizationUser && $user->organization()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $organization = $user instanceof OrganizationUser
            ? $user->organization()->first()
            : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => [
                'required',
                'string',
                'max:32',
                Rule::unique('organizations', 'tax_number')->ignore($organization),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => trans('ui.organization.name'),
            'tax_number' => trans('ui.organization.taxNumber'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->string('name')->toString()),
            'tax_number' => trim($this->string('tax_number')->toString()),
        ]);
    }
}
