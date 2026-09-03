<?php

namespace Tests\Feature\IdentityAccess;

use App\Actions\IdentityAccess\RegisterOrganizationUser;
use App\Localization\LocaleManager;
use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\Concerns\InteractsWithLanguages;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithLanguages;

    public function test_an_organization_user_can_register_and_email_verification_is_enqueued(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);

        $response = $this->withSession([LocaleManager::SESSION_KEY => 'tr'])
            ->post(route('register.store'), [
                'name' => '  Sinan Daryaken  ',
                'email' => '  SINAN@EXAMPLE.COM ',
                'password' => 'strong-password',
                'password_confirmation' => 'strong-password',
            ]);

        $user = OrganizationUser::query()->where('email', 'sinan@example.com')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('Sinan Daryaken', $user->name);
        $this->assertTrue(Hash::check('strong-password', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('processing_tasks', [
            'type' => ProcessingTask::TYPE_EMAIL_VERIFICATION,
            'payload_version' => 2,
            'dedupe_key' => ProcessingTask::TYPE_EMAIL_VERIFICATION.':'.$user->getKey(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $task = ProcessingTask::query()->where('type', ProcessingTask::TYPE_EMAIL_VERIFICATION)->firstOrFail();

        $this->assertIdentityMailPayload(
            $task->payload,
            (string) $user->getKey(),
            'tr',
        );
        $this->assertSame(2, $task->payload_version);
    }

    public function test_registration_rejects_an_existing_canonical_email(): void
    {
        OrganizationUser::factory()->create(['email' => 'sinan@example.com']);

        $response = $this->from(route('register.create'))->post(route('register.store'), [
            'name' => 'Sinan Daryaken',
            'email' => ' SINAN@EXAMPLE.COM ',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirect(route('register.create'));
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_rolls_back_the_user_and_task_when_task_creation_fails(): void
    {
        ProcessingTask::created(function (): void {
            throw new RuntimeException('Simulated processing task failure.');
        });

        try {
            $this->app->make(RegisterOrganizationUser::class)->handle([
                'name' => 'Sinan Daryaken',
                'email' => 'sinan@example.com',
                'password' => 'strong-password',
            ], 'en');

            $this->fail('Registration should fail when processing task creation fails.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated processing task failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('organization_users', [
            'email' => 'sinan@example.com',
        ]);
        $this->assertDatabaseMissing('processing_tasks', [
            'type' => ProcessingTask::TYPE_EMAIL_VERIFICATION,
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
