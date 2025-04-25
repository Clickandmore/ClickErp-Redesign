<?php

namespace App\Http\Controllers\Admin;

use App\Models\NewSettings;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{

    public function index()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            $settings = new NewSettings();

            $fields = [
                'warrenty_period',
                'bank_transfer_data',
                'sadad_transfer_data',
                'show_popup',
                'popup_image',
            ];

            $data = [];
            foreach ($fields as $field) {
                $data[$field] = $settings->getSetting($field);
            }

            // For debugging purposes
            Log::info('Settings loaded:', $data);

            return view('admin.settings.index', compact('data', 'fields'));
        } catch (\Exception $e) {
            Log::error('Error loading settings: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.error_loading_settings'));
        }
    }

    public function store(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            $settings = new NewSettings();

            // Validate the request
            $request->validate([
                'warrenty_period' => 'nullable|numeric|min:0',
                'bank_transfer_data' => 'nullable|string',
                'sadad_transfer_data' => 'nullable|string',
                'popup_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            DB::beginTransaction();

            // Save show_popup setting
            $show_popup = $request->has('show_popup') ? '1' : '0';
            $settings->setSetting('show_popup', $show_popup);

            // Save other text fields
            $text_fields = [
                'warrenty_period',
                'bank_transfer_data',
                'sadad_transfer_data',
            ];

            foreach ($text_fields as $field) {
                if ($request->has($field)) {
                    $settings->setSetting($field, $request->$field);
                }
            }

            // Handle popup image upload
            if ($request->hasFile('popup_image')) {
                $file = $request->file('popup_image');

                // Delete old image if exists
                $old_image = $settings->getSetting('popup_image');
                if ($old_image) {
                    Storage::delete('public/' . $old_image);
                }

                // Store new image
                $path = $file->store('images/settings', 'public');
                $settings->setSetting('popup_image', $path);
            }

            DB::commit();
            Log::info('Settings updated successfully');
            return redirect()->back()->with('success', __('admin.settings_updated_successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating settings: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.settings_update_failed'))->withInput();
        }
    }

}
