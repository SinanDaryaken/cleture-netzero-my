<?php

namespace App\Http\Controllers\Products;

use App\Actions\Products\ListOrganizationProducts;
use App\Http\Controllers\Controller;
use App\Models\OrganizationUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request, ListOrganizationProducts $products): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof OrganizationUser, 403);

        $organization = $user->organization()->first();

        if ($organization === null) {
            return redirect()
                ->route('organization.show')
                ->with('error', trans('ui.products.organizationRequired'));
        }

        return Inertia::render('products/Index', [
            'organization' => [
                'name' => $organization->name,
            ],
            ...$products->handle($organization, app()->currentLocale()),
        ]);
    }
}
