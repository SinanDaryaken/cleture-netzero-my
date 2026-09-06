<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Currency extends Model
{
    use CentralConnection, HasUuids, SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'deleted_at' => 'immutable_datetime',
        ];
    }
}
