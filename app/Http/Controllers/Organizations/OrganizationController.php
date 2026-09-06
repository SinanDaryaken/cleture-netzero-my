<?php

namespace App\Http\Controllers\Organizations;

use App\Actions\Organizations\CreateOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\StoreOrganizationRequest;
use App\Http\Requests\Organizations\UpdateOrganizationRequest;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var OrganizationUser $user */
        $user = $request->user();

        $organization = $user->organization()->with('tenant')->first();

        return Inertia::render('organizations/Organization', [
            'organization' => $organization === null ? null : [
                'name' => $organization->name,
                'taxNumber' => $organization->tax_number,
                'netZeroRequested' => $organization->netzero_requested,
                'tenant' => $organization->tenant === null ? null : [
                    'provisioningStatus' => $organization->tenant->provisioning_status->value,
                    'active' => $organization->tenant->active,
                    'available' => $organization->tenant->isAvailable(),
                ],
            ],
        ]);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganization $createOrganization): RedirectResponse
    {
        /** @var OrganizationUser $user */
        $user = $request->user();

        $createOrganization->handle($user, $request->validated());

        return redirect()->route('organization.show')->with(
            'status',
            trans('ui.organization.created'),
        );
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        /** @var OrganizationUser $user */
        $user = $request->user();

        $user->organization()->firstOrFail()->update($request->validated());

        return redirect()->route('organization.show')->with(
            'status',
            trans('ui.organization.updated'),
        );
    }
}
