<?php

namespace App\Http\Controllers\Organizations;

use App\Actions\Organizations\RequestNetZeroProvisioning;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizations\RequestNetZeroProvisioningRequest;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;

class NetZeroProvisioningController extends Controller
{
    public function __invoke(
        RequestNetZeroProvisioningRequest $request,
        RequestNetZeroProvisioning $requestProvisioning,
    ): RedirectResponse {
        /** @var OrganizationUser $user */
        $user = $request->user();

        $requestProvisioning->handle($user->organization()->firstOrFail());

        return redirect()->route('organization.show')->with(
            'status',
            trans('ui.organization.netZeroRequested'),
        );
    }
}
