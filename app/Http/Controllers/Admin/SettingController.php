<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'settings' => Setting::current(),
            'locales' => Locales::available(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        // fill()->save(), not update(): current() hands back an unsaved row
        // when the settings row is missing, and update() would refuse that.
        Setting::current()->fill([
            ...$request->safe(['site_name', 'primary_color', 'locales', 'default_locale']),
            'under_construction' => $request->boolean('under_construction'),
            'show_login_link' => $request->boolean('show_login_link'),
        ])->save();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', __(':Name updated.', ['name' => __('settings')]));
    }
}
