<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreOrganizationUnitTypeRequest;
use App\Http\Requests\Tenant\UpdateOrganizationUnitTypeRequest;
use App\Models\Tenant\OrganizationUnitType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationUnitTypeController extends Controller
{
    public function index(): Response
    {
        $organizationUnitTypes = OrganizationUnitType::query()
            ->select(['id', 'name', 'active', 'sort_order'])
            ->withCount('organizationalUnits')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (OrganizationUnitType $organizationUnitType): array => [
                'id' => (string) $organizationUnitType->getKey(),
                'name' => $organizationUnitType->name,
                'active' => $organizationUnitType->active,
                'sortOrder' => $organizationUnitType->sort_order,
                'unitsCount' => $organizationUnitType->organizational_units_count,
            ]);

        return Inertia::render('tenant-organization-unit-types/Index', [
            'organizationUnitTypes' => $organizationUnitTypes,
        ]);
    }

    public function store(StoreOrganizationUnitTypeRequest $request): RedirectResponse
    {
        OrganizationUnitType::query()->create($request->validated());

        return to_route('tenant.organization-unit-types.index')
            ->with('status', trans('ui.tenantOrganizationUnitTypes.created'));
    }

    public function update(
        UpdateOrganizationUnitTypeRequest $request,
        string $organizationUnitType,
    ): RedirectResponse {
        OrganizationUnitType::query()->findOrFail($organizationUnitType)->update(
            $request->validated(),
        );

        return to_route('tenant.organization-unit-types.index')
            ->with('status', trans('ui.tenantOrganizationUnitTypes.updated'));
    }

    public function destroy(string $organizationUnitType): RedirectResponse
    {
        $type = OrganizationUnitType::query()->findOrFail($organizationUnitType);

        if ($type->organizationalUnits()->exists()) {
            return back()->with('error', trans('ui.tenantOrganizationUnitTypes.inUse'));
        }

        try {
            $type->delete();
        } catch (QueryException $exception) {
            if (! str_starts_with((string) $exception->getCode(), '23')) {
                throw $exception;
            }

            return back()->with('error', trans('ui.tenantOrganizationUnitTypes.inUse'));
        }

        return to_route('tenant.organization-unit-types.index')
            ->with('status', trans('ui.tenantOrganizationUnitTypes.deleted'));
    }
}
