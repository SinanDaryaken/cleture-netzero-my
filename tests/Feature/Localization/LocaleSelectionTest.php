<?php

namespace Tests\Feature\Localization;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithLanguages;
use Tests\TestCase;

class LocaleSelectionTest extends TestCase
{
    use DatabaseTransactions, InteractsWithLanguages;

    public function test_active_languages_are_shared_and_the_main_language_is_selected_by_default(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);

        $this->get(route('login.create'))->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('localization.locale', 'tr')
                ->where('localization.languages.0.code', 'tr')
                ->where('localization.languages.1.code', 'en')
                ->where('localization.translations.login.title', 'Tekrar hoş geldiniz'),
        );
    }

    public function test_an_active_language_can_be_selected_for_following_requests(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('en', 'English', active: true);

        $this->from(route('login.create'))
            ->post(route('locale.update'), ['locale' => 'EN'])
            ->assertRedirect(route('login.create'))
            ->assertSessionHas('locale', 'en');

        $this->withSession(['locale' => 'en'])
            ->get(route('login.create'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('localization.locale', 'en')
                ->where('localization.translations.login.title', 'Welcome back'));
    }

    public function test_an_inactive_language_cannot_be_selected(): void
    {
        $this->ensureLanguage('tr', 'Türkçe', active: true, main: true);
        $this->ensureLanguage('de', 'Deutsch', active: false);

        $this->from(route('login.create'))
            ->post(route('locale.update'), ['locale' => 'de'])
            ->assertRedirect(route('login.create'))
            ->assertSessionHasErrors('locale');
    }
}
