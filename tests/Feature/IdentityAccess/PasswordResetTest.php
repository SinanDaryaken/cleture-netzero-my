<?php

namespace Tests\Feature\IdentityAccess;

use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_known_email_enqueues_a_secret_free_password_reset_task(): void
    {
        $user = OrganizationUser::factory()->create(['email' => 'sinan@example.com']);

        $response = $this->post(route('password.email'), [
            'email' => ' SINAN@EXAMPLE.COM ',
        ]);

        $response->assertSessionHas(
            'status',
            __('ui.forgotPassword.status'),
        );

        $task = ProcessingTask::query()
            ->where('type', ProcessingTask::TYPE_PASSWORD_RESET)
            ->firstOrFail();

        $this->assertSame(
            [
                'organizationUserId' => (string) $user->getKey(),
            ],
            $task->payload,
        );
        $this->assertSame(1, $task->payload_version);
        $this->assertSame('pending', $task->status);
        $this->assertSame(0, $task->attempts);
        $this->assertArrayNotHasKey('email', $task->payload);
        $this->assertArrayNotHasKey('token', $task->payload);
        $this->assertArrayNotHasKey('password', $task->payload);
    }

    public function test_an_unknown_email_receives_the_same_generic_response(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ]);

        $response->assertSessionHas(
            'status',
            __('ui.forgotPassword.status'),
        );
        $this->assertDatabaseMissing('processing_tasks', [
            'type' => ProcessingTask::TYPE_PASSWORD_RESET,
        ]);
    }

    public function test_an_existing_active_reset_task_is_not_duplicated_or_modified(): void
    {
        $user = OrganizationUser::factory()->create(['email' => 'sinan@example.com']);
        $availableAt = now()->subMinute()->startOfSecond();
        $task = ProcessingTask::query()->create([
            'type' => ProcessingTask::TYPE_PASSWORD_RESET,
            'payload_version' => ProcessingTask::IDENTITY_MAIL_PAYLOAD_VERSION,
            'tenant_id' => null,
            'payload' => ['organizationUserId' => (string) $user->getKey()],
            'dedupe_key' => ProcessingTask::TYPE_PASSWORD_RESET.':'.$user->getKey(),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => $availableAt,
        ]);

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $task->refresh();

        $this->assertSame(1, $task->payload_version);
        $this->assertSame([
            'organizationUserId' => (string) $user->getKey(),
        ], $task->payload);
        $this->assertSame('pending', $task->status);
        $this->assertSame(0, $task->attempts);
        $this->assertTrue($availableAt->equalTo($task->available_at));
        $this->assertSame(1, ProcessingTask::query()->where('type', ProcessingTask::TYPE_PASSWORD_RESET)->count());
    }

    public function test_a_valid_password_broker_token_resets_the_password_and_invalidates_old_sessions(): void
    {
        $user = OrganizationUser::factory()->create([
            'email' => 'sinan@example.com',
            'password' => 'old-password',
            'auth_version' => 4,
        ]);
        $token = Password::broker('organization_users')->createToken($user);

        $response = $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $response->assertRedirect(route('login.create'));

        $user->refresh();

        $this->assertTrue(Hash::check('new-strong-password', $user->password));
        $this->assertSame(5, $user->auth_version);
        $this->assertDatabaseMissing('organization_user_password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_reset_pages_prevent_the_reset_link_from_being_sent_as_a_referrer(): void
    {
        $this->get(route('password.reset', [
            'token' => 'secret-reset-token',
            'email' => 'sinan@example.com',
        ]))->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_invalid_reset_attempts_do_not_reveal_if_the_email_exists(): void
    {
        $user = OrganizationUser::factory()->create(['email' => 'sinan@example.com']);
        $expectedMessage = __('ui.resetPassword.invalidLink');

        $knownEmailResponse = $this->from(route('password.reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
        ]))->post(route('password.update'), [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $unknownEmailResponse = $this->from(route('password.reset', [
            'token' => 'invalid-token',
            'email' => 'unknown@example.com',
        ]))->post(route('password.update'), [
            'email' => 'unknown@example.com',
            'token' => 'invalid-token',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ]);

        $knownEmailResponse->assertSessionHasErrors(['email' => $expectedMessage]);
        $unknownEmailResponse->assertSessionHasErrors(['email' => $expectedMessage]);
    }

    public function test_reset_link_requests_are_rate_limited_per_normalized_email(): void
    {
        OrganizationUser::factory()->create(['email' => 'sinan@example.com']);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
            ->post(route('password.email'), ['email' => 'SINAN@EXAMPLE.COM'])
            ->assertRedirect();

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->post(route('password.email'), ['email' => ' sinan@example.com '])
            ->assertTooManyRequests();
    }

    public function test_password_reset_attempts_are_rate_limited_per_normalized_email(): void
    {
        $payload = [
            'email' => 'SINAN@EXAMPLE.COM',
            'token' => 'invalid-token',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ];

        foreach (range(1, 5) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.(20 + $attempt)])
                ->post(route('password.update'), $payload)
                ->assertRedirect();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.30'])
            ->post(route('password.update'), $payload)
            ->assertTooManyRequests();
    }

    public function test_password_reset_requires_at_least_twelve_characters(): void
    {
        $user = OrganizationUser::factory()->create([
            'email' => 'sinan@example.com',
            'password' => 'old-password',
        ]);
        $token = Password::broker('organization_users')->createToken($user);

        $this->from(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]))->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'eleven-chrs',
            'password_confirmation' => 'eleven-chrs',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertDatabaseHas('organization_user_password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}
