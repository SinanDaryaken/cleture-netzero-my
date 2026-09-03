<?php

namespace Tests\Feature\IdentityAccess;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\OrganizationUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_verified_organization_user_can_log_in_and_log_out(): void
    {
        $user = OrganizationUser::factory()->create([
            'email' => 'sinan@example.com',
            'password' => 'strong-password',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => ' SINAN@EXAMPLE.COM ',
            'password' => 'strong-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas(
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION,
            $user->auth_version,
        );
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login.create'));
        $this->assertGuest();
    }

    public function test_an_unverified_user_is_sent_to_the_verification_notice(): void
    {
        $user = OrganizationUser::factory()->unverified()->create([
            'password' => 'strong-password',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'strong-password',
        ])->assertRedirect(route('verification.notice'));
    }

    public function test_invalid_credentials_do_not_reveal_which_field_failed(): void
    {
        OrganizationUser::factory()->create(['email' => 'sinan@example.com']);

        $response = $this->from(route('login.create'))->post(route('login.store'), [
            'email' => 'sinan@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login.create'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_session_with_an_old_auth_version_is_invalidated(): void
    {
        $user = OrganizationUser::factory()->create(['auth_version' => 2]);

        $response = $this
            ->actingAs($user)
            ->withSession([AuthenticateOrganizationUser::SESSION_AUTH_VERSION => 1])
            ->get(route('dashboard'));

        $response->assertRedirect(route('login.create'));
        $this->assertGuest();
    }
}
