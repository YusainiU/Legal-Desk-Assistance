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
            'taskWarningHours' => FirmSetting::taskWarningHours(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = $request->validate(['firm_name' => ['present', 'nullable', 'string', 'max:100'], 'task_warning_hours' => ['required', 'integer', 'min:1', 'max:720']]);

        FirmSetting::setName($settings['firm_name']);
        FirmSetting::setTaskWarningHours((int) $settings['task_warning_hours']);

        return to_route('settings.firm.edit')->with('status', 'Firm settings saved.');
    }
}
