<?php

namespace App\Localization;

use App\Models\Language;
use Illuminate\Http\Request;

class LocaleManager
{
    public const SESSION_KEY = 'locale';

    /** @var list<array{code: string, nativeName: string}>|null */
    private ?array $languageOptions = null;

    /** @return list<array{code: string, nativeName: string}> */
    public function activeLanguageOptions(): array
    {
        if ($this->languageOptions !== null) {
            return $this->languageOptions;
        }

        return $this->languageOptions = Language::query()
            ->active()
            ->orderByDesc('main')
            ->orderBy('native_name')
            ->get(['code', 'native_name'])
            ->map(fn (Language $language): array => [
                'code' => $language->code,
                'nativeName' => $language->native_name,
            ])
            ->all();
    }

    public function resolve(Request $request): string
    {
        $availableCodes = array_column($this->activeLanguageOptions(), 'code');
        $selectedLocale = $request->session()->get(self::SESSION_KEY);

        if (is_string($selectedLocale) && in_array($selectedLocale, $availableCodes, true)) {
            return $selectedLocale;
        }

        $defaultLocale = $availableCodes[0] ?? (string) config('app.locale');
        $request->session()->put(self::SESSION_KEY, $defaultLocale);

        return $defaultLocale;
    }

    public function select(Request $request, string $locale): void
    {
        $request->session()->put(self::SESSION_KEY, $locale);
    }
}
