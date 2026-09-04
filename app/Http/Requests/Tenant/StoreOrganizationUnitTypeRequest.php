<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationUnitTypeRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant.organization_unit_types', 'name'),
            ],
            'active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => trans('ui.tenantOrganizationUnitTypes.name'),
            'active' => trans('ui.tenantOrganizationUnitTypes.status'),
            'sort_order' => trans('ui.tenantOrganizationUnitTypes.sortOrder'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->string('name')->toString()),
        ]);
    }
}
