<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\DatabaseConfig;

trait InteractsWithTenantDatabase
{
    /** @var list<string> */
    private array $tenantDatabasePaths = [];

    protected function configureTenantDatabaseTesting(): void
    {
        $this->ensureCentralTenantSchemaExists();

        config([
            'database.connections.tenant_testing' => [
                'driver' => 'sqlite',
                'database' => database_path('tenant-testing-template.sqlite'),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'tenancy.database.template_tenant_connection' => 'tenant_testing',
        ]);

        DatabaseConfig::generateDatabaseNamesUsing(
            static fn (TenantWithDatabase $tenant): string => 'tenant-testing-'.str_replace(
                '-',
                '',
                (string) $tenant->getTenantKey(),
            ).'.sqlite',
        );
    }

    private function ensureCentralTenantSchemaExists(): void
    {
        $centralSchema = Schema::connection((string) config('tenancy.database.central_connection'));

        if (! $centralSchema->hasColumn('organizations', 'netzero_requested')) {
            $centralSchema->table('organizations', function (Blueprint $table): void {
                $table->boolean('netzero_requested')->default(false);
            });
        }

        if (! $centralSchema->hasTable('tenants')) {
            $centralSchema->create('tenants', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')
                    ->unique()
                    ->constrained()
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
                $table->string('provisioning_status')->default('pending');
                $table->boolean('active')->default(false);
                $table->string('schema_version')->nullable();
                $table->timestampsTz();
            });
        }
    }

    protected function createTenantDatabase(Tenant $tenant): void
    {
        $databasePath = database_path($tenant->database()->getName());

        touch($databasePath);
        $this->tenantDatabasePaths[] = $databasePath;

        tenancy()->initialize($tenant);

        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->timestampsTz();
        });

        Schema::connection('tenant')->create(
            'organization_unit_types',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->boolean('active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestampsTz();
            },
        );

        Schema::connection('tenant')->create(
            'organizational_units',
            function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('parent_id')
                    ->nullable()
                    ->constrained('organizational_units')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
                $table->foreignUuid('organization_unit_type_id')
                    ->nullable()
                    ->constrained('organization_unit_types')
                    ->restrictOnUpdate()
                    ->restrictOnDelete();
                $table->string('name');
                $table->boolean('mark_as_company')->default(false);
                $table->boolean('mark_as_facility')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestampsTz();
            },
        );

        tenancy()->end();
    }

    protected function cleanUpTenantDatabases(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        foreach ($this->tenantDatabasePaths as $databasePath) {
            if (is_file($databasePath)) {
                unlink($databasePath);
            }
        }
    }
}
