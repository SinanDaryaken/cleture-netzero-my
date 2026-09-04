<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Validation\Rule;

class UpdateOrganizationUnitTypeRequest extends StoreOrganizationUnitTypeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('tenant.organization_unit_types', 'name')
                ->ignore((string) $this->route('organizationUnitType')),
        ];

        return $rules;
    }
}
