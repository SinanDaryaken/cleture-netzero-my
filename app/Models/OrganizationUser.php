<?php

namespace App\Models;

use Database\Factories\OrganizationUserFactory;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class OrganizationUser extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<OrganizationUserFactory> */
    use CentralConnection, HasFactory, HasUuids, MustVerifyEmailTrait, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'auth_version' => 1,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => self::normalizeEmail($value),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'auth_version' => 'integer',
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
        ];
    }
}
