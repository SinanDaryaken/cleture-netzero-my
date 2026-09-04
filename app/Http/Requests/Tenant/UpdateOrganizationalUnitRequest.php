<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\OrganizationalUnit;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOrganizationalUnitRequest extends StoreOrganizationalUnitRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $currentTypeId = OrganizationalUnit::query()
            ->whereKey((string) $this->route('organizationalUnit'))
            ->value('organization_unit_type_id');
        $rules['organization_unit_type_id'] = [
            'nullable',
            'uuid',
            Rule::exists('tenant.organization_unit_types', 'id')
                ->where(function (Builder $query) use ($currentTypeId): void {
                    $query->where('active', true);

                    if (is_string($currentTypeId)) {
                        $query->orWhere('id', $currentTypeId);
                    }
                }),
        ];

        return $rules;
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if ($validator->errors()->has('parent_id')) {
                    return;
                }

                $unitId = (string) $this->route('organizationalUnit');
                $parentId = $this->input('parent_id');
                $visitedIds = [];

                while (is_string($parentId) && $parentId !== '') {
                    if ($parentId === $unitId || isset($visitedIds[$parentId])) {
                        $validator->errors()->add(
                            'parent_id',
                            trans('ui.tenantOrganizationalUnits.parentCycle'),
                        );

                        return;
                    }

                    $visitedIds[$parentId] = true;
                    $parentId = OrganizationalUnit::query()
                        ->whereKey($parentId)
                        ->value('parent_id');
                }
            },
        ];
    }
}
