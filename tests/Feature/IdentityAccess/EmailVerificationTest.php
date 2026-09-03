<?php

namespace Tests\Feature\IdentityAccess;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Localization\LocaleManager;
use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithLanguages;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithLanguages;

    public function test_a_valid_worker_generated_token_verifies_the_email(): void
    {
        $user = OrganizationUser::factory()->unverified()->create();
        $plainToken = 'plain-token-only-used-in-the-link';

        DB::table('organization_user_email_verification_tokens')->insert([
            'organization_user_id' => $user->getKey(),
            'token' => Hash::make($plainToken),
            'created_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->get(route('verification.verify', [$user, $plainToken]));

        $response->assertRedirect(route('login.create'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('organization_user_email_verification_tokens', [
            'organization_user_id' => $user->getKey(),
        ]);
    }

    public function test_an_expired_token_does_not_verify_the_email(): void
    {
        $user = OrganizationUser::factory()->unverified()->create();

        DB::table('organization_user_email_verification_tokens')->insert([
            'organization_user_id' => $user->getKey(),
            'token' => Hash::make('expired-token'),
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);

        $this->get(route('verification.verify', [$user, 'expired-token']))
            ->assertRedirect(route('login.create'));

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resending_does_not_duplicate_an_active_processing_task(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);
        $user = OrganizationUser::factory()->unverified()->create();
        $session = [
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
            LocaleManager::SESSION_KEY => 'en',
        ];

        $this->actingAs($user)->withSession($session)->post(route('verification.send'));
        $this->actingAs($user)->withSession($session)->post(route('verification.send'));

        $this->assertSame(
            1,
            ProcessingTask::query()
                ->where('type', ProcessingTask::TYPE_EMAIL_VERIFICATION)
                ->where('dedupe_key', ProcessingTask::TYPE_EMAIL_VERIFICATION.':'.$user->getKey())
                ->count(),
        );

        $task = ProcessingTask::query()
            ->where('type', ProcessingTask::TYPE_EMAIL_VERIFICATION)
            ->firstOrFail();

        $this->assertSame(2, $task->payload_version);
        $this->assertIdentityMailPayload($task->payload, (string) $user->getKey(), 'en');
        $this->assertSame('pending', $task->status);
        $this->assertSame(0, $task->attempts);
    }

    public function test_resending_does_not_modify_an_active_version_two_task(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);
        $user = OrganizationUser::factory()->unverified()->create();
        $availableAt = now()->subMinutes(2)->startOfSecond();
        $dispatchedAt = now()->subMinute()->startOfSecond();
        $claimedAt = now()->subSeconds(30)->startOfSecond();
        $leaseExpiresAt = now()->addSeconds(30)->startOfSecond();
        $task = new ProcessingTask;
        $task->forceFill([
            'type' => ProcessingTask::TYPE_EMAIL_VERIFICATION,
            'payload_version' => 2,
            'tenant_id' => null,
            'payload' => [
                'organizationUserId' => (string) $user->getKey(),
                'locale' => 'tr',
            ],
            'dedupe_key' => ProcessingTask::TYPE_EMAIL_VERIFICATION.':'.$user->getKey(),
            'status' => 'processing',
            'attempts' => 2,
            'available_at' => $availableAt,
            'dispatched_at' => $dispatchedAt,
            'dispatch_token' => (string) Str::uuid7(),
            'claimed_at' => $claimedAt,
            'lease_expires_at' => $leaseExpiresAt,
            'claimed_by' => 'worker-1',
        ]);
        $task->save();
        $task->refresh();
        $originalAttributes = $task->getAttributes();
        $session = [
            AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version,
            LocaleManager::SESSION_KEY => 'en',
        ];

        $this->actingAs($user)->withSession($session)->post(route('verification.send'));

        $task->refresh();

        $this->assertSame($originalAttributes, $task->getAttributes());
        $this->assertSame(
            1,
            ProcessingTask::query()
                ->where('dedupe_key', ProcessingTask::TYPE_EMAIL_VERIFICATION.':'.$user->getKey())
                ->count(),
        );
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
