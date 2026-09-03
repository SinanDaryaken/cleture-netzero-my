<?php

namespace Tests\Feature\IdentityAccess;

use App\Models\OrganizationUser;
use App\Models\ProcessingTask;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_organization_user_can_register_and_email_verification_is_enqueued(): void
    {
        $response = $this->post(route('register.store'), [
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
            'payload_version' => 1,
            'dedupe_key' => ProcessingTask::TYPE_EMAIL_VERIFICATION.':'.$user->getKey(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $task = ProcessingTask::query()->where('type', ProcessingTask::TYPE_EMAIL_VERIFICATION)->firstOrFail();

        $this->assertSame(
            [
                'organizationUserId' => (string) $user->getKey(),
            ],
            $task->payload,
        );
        $this->assertSame(1, $task->payload_version);
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
}
