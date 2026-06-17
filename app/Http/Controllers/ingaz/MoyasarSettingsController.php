<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Support\MoyasarSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MoyasarSettingsController extends Controller
{
    public function edit()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MOYASAR_SETTINGS)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        return view('admin.ingaz.moyasar-settings.edit', [
            'moyasarSettings' => MoyasarSettings::current(),
        ]);
    }

    public function update(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::MOYASAR_SETTINGS)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        $validated = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'base_url' => 'required|url|max:255',
            'publishable_key' => 'nullable|string|max:1000',
            'secret_key' => 'nullable|string|max:1000',
            'webhook_secret' => 'nullable|string|max:1000',
        ], [
            'base_url.required' => 'رابط Moyasar API مطلوب.',
            'base_url.url' => 'رابط Moyasar API غير صحيح.',
        ]);

        $current = MoyasarSettings::current();
        $willBeEnabled = (bool) ($validated['is_enabled'] ?? false);
        $publishableKey = trim((string) ($validated['publishable_key'] ?? '')) ?: $current['publishable_key'];
        $secretKey = trim((string) ($validated['secret_key'] ?? '')) ?: $current['secret_key'];

        if ($willBeEnabled && ($publishableKey === '' || $secretKey === '')) {
            $messages = [];

            if ($publishableKey === '') {
                $messages['publishable_key'] = 'مفتاح النشر مطلوب عند تفعيل ميسر.';
            }

            if ($secretKey === '') {
                $messages['secret_key'] = 'المفتاح السري مطلوب عند تفعيل ميسر.';
            }

            throw ValidationException::withMessages($messages);
        }

        try {
            MoyasarSettings::persist($validated, auth('admin')->id());

            return redirect()
                ->route('moyasar-settings.edit')
                ->with('success', 'تم حفظ إعدادات ميسر بنجاح.');
        } catch (\Throwable $exception) {
            Log::error('Failed to update Moyasar settings', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('moyasar-settings.edit')
                ->withInput()
                ->with('error', 'تعذر حفظ إعدادات ميسر حالياً.');
        }
    }
}
