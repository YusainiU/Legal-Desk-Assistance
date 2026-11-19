<?php

namespace App\Http\Controllers;

use App\Models\FirmSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FirmSettingsController
{
    public function edit(): View
    {
        return view('settings.firm', [
            'firmName' => FirmSetting::displayName(),
            'defaultName' => FirmSetting::DEFAULT_NAME,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = $request->validate(['firm_name' => ['present', 'nullable', 'string', 'max:100']]);

        FirmSetting::setName($settings['firm_name']);

        return to_route('settings.firm.edit')->with('status', 'Firm name saved.');
    }
}
