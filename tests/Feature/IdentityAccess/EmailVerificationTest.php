<?php

namespace Tests\Feature\IdentityAccess;

use App\Actions\IdentityAccess\AuthenticateOrganizationUser;
use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use DatabaseTransactions;

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
        $user = OrganizationUser::factory()->unverified()->create();
        $session = [AuthenticateOrganizationUser::SESSION_AUTH_VERSION => $user->auth_version];

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

        $this->assertSame(1, $task->payload_version);
        $this->assertSame([
            'organizationUserId' => (string) $user->getKey(),
        ], $task->payload);
        $this->assertSame('pending', $task->status);
        $this->assertSame(0, $task->attempts);
    }
}
