<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantUserRequest;
use App\Http\Requests\Tenant\UpdateTenantUserRequest;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantUserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());
        $active = $request->string('active')->toString();

        $users = User::query()
            ->select(['id', 'name', 'email', 'active', 'created_at'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%");
                });
            })
            ->when(in_array($active, ['active', 'inactive'], true), function (Builder $query) use ($active): void {
                $query->where('active', $active === 'active');
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => (string) $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'active' => $user->active,
                'createdAt' => $user->created_at?->toAtomString(),
            ]);

        return Inertia::render('tenant-users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'active' => $active,
            ],
        ]);
    }

    public function store(StoreTenantUserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());

        return to_route('tenant.users.index')
            ->with('status', trans('ui.tenantUsers.created'));
    }

    public function update(
        UpdateTenantUserRequest $request,
        string $tenantUser,
    ): RedirectResponse {
        $user = User::query()->findOrFail($tenantUser);
        $attributes = $request->validated();

        if (empty($attributes['password'])) {
            unset($attributes['password']);
        }

        $user->update($attributes);

        return to_route('tenant.users.index')
            ->with('status', trans('ui.tenantUsers.updated'));
    }

    public function destroy(string $tenantUser): RedirectResponse
    {
        User::query()->findOrFail($tenantUser)->delete();

        return to_route('tenant.users.index')
            ->with('status', trans('ui.tenantUsers.deleted'));
    }
}
