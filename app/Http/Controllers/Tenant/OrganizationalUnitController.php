<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreOrganizationalUnitRequest;
use App\Http\Requests\Tenant\UpdateOrganizationalUnitRequest;
use App\Models\Tenant\OrganizationalUnit;
use App\Models\Tenant\OrganizationUnitType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationalUnitController extends Controller
{
    public function index(): Response
    {
        $units = OrganizationalUnit::query()
            ->select([
                'id',
                'parent_id',
                'organization_unit_type_id',
                'name',
                'mark_as_company',
                'mark_as_facility',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (OrganizationalUnit $unit): array => [
                'id' => (string) $unit->getKey(),
                'parentId' => $unit->parent_id === null ? null : (string) $unit->parent_id,
                'organizationUnitTypeId' => $unit->organization_unit_type_id === null
                    ? null
                    : (string) $unit->organization_unit_type_id,
                'name' => $unit->name,
                'markAsCompany' => $unit->mark_as_company,
                'markAsFacility' => $unit->mark_as_facility,
                'sortOrder' => $unit->sort_order,
            ]);

        return Inertia::render('tenant-organizational-units/Index', [
            'units' => $units,
            'organizationUnitTypes' => OrganizationUnitType::query()
                ->select(['id', 'name', 'active'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->orderBy('id')
                ->get()
                ->map(fn (OrganizationUnitType $type): array => [
                    'id' => (string) $type->getKey(),
                    'name' => $type->name,
                    'active' => $type->active,
                ]),
        ]);
    }

    public function store(StoreOrganizationalUnitRequest $request): RedirectResponse
    {
        OrganizationalUnit::query()->create($request->validated());

        return to_route('tenant.organizational-units.index')
            ->with('status', trans('ui.tenantOrganizationalUnits.created'));
    }

    public function update(
        UpdateOrganizationalUnitRequest $request,
        string $organizationalUnit,
    ): RedirectResponse {
        $unit = OrganizationalUnit::query()->findOrFail($organizationalUnit);
        $unit->update($request->validated());

        return to_route('tenant.organizational-units.index')
            ->with('status', trans('ui.tenantOrganizationalUnits.updated'));
    }
}
