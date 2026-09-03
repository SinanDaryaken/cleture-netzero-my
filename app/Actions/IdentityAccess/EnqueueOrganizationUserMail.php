<?php

namespace App\Actions\IdentityAccess;

use App\Localization\LocaleManager;
use App\Models\OrganizationUser;
use App\Models\ProcessingTask;

class EnqueueOrganizationUserMail
{
    public function __construct(private readonly LocaleManager $locales) {}

    public function emailVerification(OrganizationUser $user, string $locale): ProcessingTask
    {
        return $this->enqueue(
            user: $user,
            type: ProcessingTask::TYPE_EMAIL_VERIFICATION,
            locale: $locale,
        );
    }

    public function passwordReset(OrganizationUser $user, string $locale): ProcessingTask
    {
        return $this->enqueue(
            user: $user,
            type: ProcessingTask::TYPE_PASSWORD_RESET,
            locale: $locale,
        );
    }

    public function passwordChanged(OrganizationUser $user, string $locale): ProcessingTask
    {
        return $this->enqueue(
            user: $user,
            type: ProcessingTask::TYPE_PASSWORD_CHANGED,
            locale: $locale,
        );
    }

    private function enqueue(OrganizationUser $user, string $type, string $locale): ProcessingTask
    {
        return ProcessingTask::query()->firstOrCreate(
            ['dedupe_key' => $type.':'.$user->getKey()],
            [
                'type' => $type,
                'payload_version' => ProcessingTask::IDENTITY_MAIL_PAYLOAD_VERSION,
                'tenant_id' => null,
                'payload' => [
                    'organizationUserId' => (string) $user->getKey(),
                    'locale' => $this->locales->identityMailLocale($locale),
                ],
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => now(),
            ],
        );
    }
}
