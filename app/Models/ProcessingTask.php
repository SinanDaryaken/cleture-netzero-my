<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProcessingTask extends Model
{
    use HasUuids;

    public const TYPE_EMAIL_VERIFICATION = 'organization-user.email-verification';

    public const TYPE_PASSWORD_CHANGED = 'organization-user.password-changed';

    public const TYPE_PASSWORD_RESET = 'organization-user.password-reset';

    public const IDENTITY_MAIL_PAYLOAD_VERSION = 2;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'payload_version',
        'tenant_id',
        'payload',
        'dedupe_key',
        'status',
        'attempts',
        'available_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'payload_version' => 'integer',
            'attempts' => 'integer',
            'available_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'lease_expires_at' => 'immutable_datetime',
        ];
    }
}
