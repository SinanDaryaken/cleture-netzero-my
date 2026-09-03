<?php

namespace App\Http\Controllers\Localization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Localization\UpdateLocaleRequest;
use App\Localization\LocaleManager;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function __invoke(
        UpdateLocaleRequest $request,
        LocaleManager $locales,
    ): RedirectResponse {
        $locales->select($request, $request->string('locale')->toString());

        return back();
    }
}
