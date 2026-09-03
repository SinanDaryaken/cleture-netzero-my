<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait InteractsWithLanguages
{
    protected function ensureLanguage(
        string $code,
        string $nativeName,
        bool $active,
        bool $main = false,
    ): void {
        $existingLanguage = DB::table('languages')->where('code', $code)->first();
        $attributes = [
            'name' => $nativeName,
            'native_name' => $nativeName,
            'active' => $active,
            'main' => $main,
            'updated_at' => now(),
            'deleted_at' => null,
        ];

        if ($existingLanguage !== null) {
            DB::table('languages')->where('code', $code)->update($attributes);

            return;
        }

        DB::table('languages')->insert([
            'id' => (string) Str::uuid7(),
            'code' => $code,
            'created_at' => now(),
            ...$attributes,
        ]);
    }
}
