<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrganizationalUnitRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('tenant.organizational_units', 'id'),
            ],
            'organization_unit_type_id' => [
                'nullable',
                'uuid',
                Rule::exists('tenant.organization_unit_types', 'id')
                    ->where('active', true),
            ],
            'mark_as_company' => ['required', 'boolean'],
            'mark_as_facility' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->boolean('mark_as_company') && $this->boolean('mark_as_facility')) {
                    $validator->errors()->add(
                        'mark_as_company',
                        trans('ui.tenantOrganizationalUnits.classificationConflict'),
                    );
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => trans('ui.tenantOrganizationalUnits.name'),
            'parent_id' => trans('ui.tenantOrganizationalUnits.parent'),
            'organization_unit_type_id' => trans('ui.tenantOrganizationalUnits.type'),
            'sort_order' => trans('ui.tenantOrganizationalUnits.sortOrder'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->string('name')->toString()),
        ]);
    }
}
