<?php

namespace Tests\Feature\IdentityAccess;

use App\Actions\IdentityAccess\EnqueueOrganizationUserMail;
use App\Actions\IdentityAccess\ResetOrganizationUserPassword;
use App\Localization\LocaleManager;
use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\InteractsWithLanguages;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use DatabaseTransactions, InteractsWithLanguages;

    public function test_a_known_email_enqueues_a_secret_free_password_reset_task(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);
        $user = OrganizationUser::factory()->create(['email' => 'sinan@example.com']);

        $response = $this->withSession([LocaleManager::SESSION_KEY => 'en'])
            ->post(route('password.email'), [
                'email' => ' SINAN@EXAMPLE.COM ',
            ]);

        $response->assertSessionHas(
            'status',
            __('ui.forgotPassword.status'),
        );

        $task = ProcessingTask::query()
            ->where('type', ProcessingTask::TYPE_PASSWORD_RESET)
            ->firstOrFail();

        $this->assertIdentityMailPayload(
            $task->payload,
            (string) $user->getKey(),
            'en',
        );
        $this->assertSame(2, $task->payload_version);
        $this->assertSame('pending', $task->status);
        $this->assertSame(0, $task->attempts);
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
        $availableAt = now()->subMinutes(2)->startOfSecond();
        $dispatchedAt = now()->subMinute()->startOfSecond();
        $dispatchToken = (string) Str::uuid7();
        $task = new ProcessingTask;
        $task->forceFill([
            'type' => ProcessingTask::TYPE_PASSWORD_RESET,
            'payload_version' => 1,
            'tenant_id' => null,
            'payload' => ['organizationUserId' => (string) $user->getKey()],
            'dedupe_key' => ProcessingTask::TYPE_PASSWORD_RESET.':'.$user->getKey(),
            'status' => 'queued',
            'attempts' => 0,
            'available_at' => $availableAt,
            'dispatched_at' => $dispatchedAt,
            'dispatch_token' => $dispatchToken,
            'claimed_at' => null,
            'lease_expires_at' => null,
            'claimed_by' => null,
        ]);
        $task->save();
        $task->refresh();
        $originalAttributes = $task->getAttributes();

        $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $task->refresh();

        $this->assertSame($originalAttributes, $task->getAttributes());
        $this->assertSame(1, ProcessingTask::query()->where('type', ProcessingTask::TYPE_PASSWORD_RESET)->count());
    }

    public function test_an_invalid_identity_mail_locale_uses_the_configured_fallback(): void
    {
        config(['app.fallback_locale' => 'tr']);
        $user = OrganizationUser::factory()->create();

        $task = $this->app->make(EnqueueOrganizationUserMail::class)
            ->passwordReset($user, 'de-DE');

        $this->assertIdentityMailPayload($task->payload, (string) $user->getKey(), 'tr');
    }

    public function test_a_valid_password_broker_token_resets_the_password_and_invalidates_old_sessions(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);
        $user = OrganizationUser::factory()->create([
            'email' => 'sinan@example.com',
            'password' => 'old-password',
            'auth_version' => 4,
        ]);
        $token = Password::broker('organization_users')->createToken($user);

        $response = $this->withSession([LocaleManager::SESSION_KEY => 'tr'])
            ->post(route('password.update'), [
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

        $task = ProcessingTask::query()
            ->where('type', ProcessingTask::TYPE_PASSWORD_CHANGED)
            ->firstOrFail();

        $this->assertSame(2, $task->payload_version);
        $this->assertIdentityMailPayload($task->payload, (string) $user->getKey(), 'tr');
        $this->assertSame('pending', $task->status);
        $this->assertSame(0, $task->attempts);
    }

    public function test_password_and_password_changed_task_roll_back_when_task_creation_fails(): void
    {
        $user = OrganizationUser::factory()->create([
            'email' => 'sinan@example.com',
            'password' => 'old-password',
            'auth_version' => 4,
        ]);
        $token = Password::broker('organization_users')->createToken($user);
        ProcessingTask::created(function (): void {
            throw new RuntimeException('Simulated processing task failure.');
        });

        try {
            $this->app->make(ResetOrganizationUserPassword::class)->handle(
                email: $user->email,
                password: 'new-strong-password',
                token: $token,
                locale: 'en',
            );

            $this->fail('Password reset should fail when processing task creation fails.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated processing task failure.', $exception->getMessage());
        }

        $user->refresh();

        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertSame(4, $user->auth_version);
        $this->assertDatabaseHas('organization_user_password_reset_tokens', [
            'email' => $user->email,
        ]);
        $this->assertDatabaseMissing('processing_tasks', [
            'type' => ProcessingTask::TYPE_PASSWORD_CHANGED,
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
        $this->assertDatabaseMissing('processing_tasks', [
            'type' => ProcessingTask::TYPE_PASSWORD_CHANGED,
        ]);
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

    /** @param  array<string, mixed>  $payload */
    private function assertIdentityMailPayload(array $payload, string $organizationUserId, string $locale): void
    {
        $payloadKeys = array_keys($payload);
        sort($payloadKeys);

        $this->assertSame(['locale', 'organizationUserId'], $payloadKeys);
        $this->assertSame($organizationUserId, $payload['organizationUserId']);
        $this->assertSame($locale, $payload['locale']);
    }
}
