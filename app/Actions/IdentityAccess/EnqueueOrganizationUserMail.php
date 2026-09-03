<?php

namespace App\Actions\IdentityAccess;

use App\Models\OrganizationUser;
use App\Models\ProcessingTask;

class EnqueueOrganizationUserMail
{
    public function emailVerification(OrganizationUser $user): ProcessingTask
    {
        return $this->enqueue(
            user: $user,
            type: ProcessingTask::TYPE_EMAIL_VERIFICATION,
        );
    }

    public function passwordReset(OrganizationUser $user): ProcessingTask
    {
        return $this->enqueue(
            user: $user,
            type: ProcessingTask::TYPE_PASSWORD_RESET,
        );
    }

    private function enqueue(OrganizationUser $user, string $type): ProcessingTask
    {
        return ProcessingTask::query()->firstOrCreate(
            ['dedupe_key' => $type.':'.$user->getKey()],
            [
                'type' => $type,
                'payload_version' => ProcessingTask::IDENTITY_MAIL_PAYLOAD_VERSION,
                'tenant_id' => null,
                'payload' => [
                    'organizationUserId' => (string) $user->getKey(),
                ],
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => now(),
            ],
        );
    }
}
