<?php

use App\Models\Tenant;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager;
use Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager;

return [
    'tenant_model' => Tenant::class,
    'id_generator' => null,

    'bootstrappers' => [
        DatabaseTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),
        'template_tenant_connection' => null,
        'prefix' => 'netzero_',
        'suffix' => '',
        'managers' => [
            'pgsql' => PostgreSQLDatabaseManager::class,
            'sqlite' => SQLiteDatabaseManager::class,
        ],
    ],

    'features' => [],
    'routes' => false,
];
