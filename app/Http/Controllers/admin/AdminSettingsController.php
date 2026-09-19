<?php

namespace App\Http\Controllers\admin;

use App\Models\Branch;
use App\Models\ingaz\FastNew;
use App\Models\ingaz\Nationality;
use App\Models\NewSettings;
use App\Support\AutomationSettings;
use App\Support\ZohoSalesIqSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminSettingsController extends Controller
{

    public function index()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            return $this->renderOperationsView();
        } catch (\Exception $e) {
            Log::error('Error loading settings: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.error_loading_settings'));
        }
    }

    public function announcements()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            $data = $this->getSettingsPayload();
            $recentFastNews = FastNew::query()->latest()->take(5)->get();
            $fastNewsCount = FastNew::query()->count();
            $nationalities = Nationality::query()->ordered()->get();

            return view('admin.settings.announcements', compact('data', 'recentFastNews', 'fastNewsCount', 'nationalities'));
        } catch (\Exception $e) {
            Log::error('Error loading announcements settings: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.error_loading_settings'));
        }
    }

    public function zoho(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_VIEW)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            $branches = $this->zohoBranches();
            $currentBranch = $this->resolveZohoBranch($request, $branches);
            $storedZohoSettings = $currentBranch?->zoho_salesiq_settings ?? [];
            $zohoEffectiveSettings = ZohoSalesIqSettings::forBranch($currentBranch);
            $missingConfig = ZohoSalesIqSettings::missingKeys($currentBranch);
            $activeSection = 'zoho';

            return view('admin.settings.zoho', compact(
                'branches',
                'currentBranch',
                'storedZohoSettings',
                'zohoEffectiveSettings',
                'missingConfig',
                'activeSection'
            ));
        } catch (\Exception $e) {
            Log::error('Error loading Zoho settings: ' . $e->getMessage());

            return redirect()->back()->with('error', __('admin.error_loading_settings'));
        }
    }

    public function updateZoho(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        $data = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'zoho_salesiq_settings' => ['nullable', 'array'],
            'zoho_salesiq_settings.salesiq_base_url' => ['nullable', 'url', 'max:500'],
            'zoho_salesiq_settings.accounts_base_url' => ['nullable', 'url', 'max:500'],
            'zoho_salesiq_settings.screen_name' => ['nullable', 'string', 'max:255'],
            'zoho_salesiq_settings.client_id' => ['nullable', 'string', 'max:255'],
            'zoho_salesiq_settings.client_secret' => ['nullable', 'string', 'max:1000'],
            'zoho_salesiq_settings.refresh_token' => ['nullable', 'string', 'max:1000'],
            'zoho_salesiq_settings.widget_url' => ['nullable', 'url', 'max:1000'],
            'zoho_salesiq_settings.timeout' => ['nullable', 'integer', 'min:5', 'max:120'],
        ]);

        try {
            $branches = $this->zohoBranches();
            $currentBranch = $this->resolveZohoBranch($request, $branches);

            if (!$currentBranch) {
                return redirect()
                    ->route('dash.settings.zoho')
                    ->with('error', 'لا توجد شركة/فرع لحفظ إعدادات Zoho عليها.');
            }

            $currentBranch->forceFill([
                'zoho_salesiq_settings' => ZohoSalesIqSettings::normalizeForStorage(
                    $data['zoho_salesiq_settings'] ?? [],
                    $currentBranch->zoho_salesiq_settings ?? []
                ),
            ])->save();

            return redirect()
                ->route('dash.settings.zoho', ['branch_id' => $currentBranch->id])
                ->with('success', __('admin.settings_updated_successfully'));
        } catch (\Exception $e) {
            Log::error('Error updating Zoho settings: ' . $e->getMessage());

            return redirect()->back()->with('error', __('admin.settings_update_failed'))->withInput();
        }
    }

    public function store(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL)) {
            return redirect()->back()->with('error', __('admin.no_permissions'));
        }

        try {
            $settings = new NewSettings();
            $section = $request->input('section', 'operations');

            $this->validateSectionRequest($request, $section);

            DB::beginTransaction();

            if ($section === 'announcements') {
                $this->storeAnnouncementsSettings($request, $settings);
            } else {
                $this->storeOperationsSettings($request, $settings);
            }

            DB::commit();
            if ($section !== 'announcements') {
                $this->refreshAutomationDrivenData();
            }
            Log::info('Settings updated successfully');
            return redirect($this->resolveRedirectRoute($section))->with('success', __('admin.settings_updated_successfully'));

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('Error updating settings: ' . $e->getMessage());
            return redirect()->back()->with('error', __('admin.settings_update_failed'))->withInput();
        }
    }

    private function renderOperationsView()
    {
        $data = $this->getSettingsPayload();

        // For debugging purposes
        Log::info('Settings loaded:', $data);

        return view('admin.settings.index', compact('data'));
    }

    private function getSettingsPayload(): array
    {
        $settings = new NewSettings();
        $fields = [
            'bank_transfer_data',
            'sadad_transfer_data',
            'show_popup',
            'popup_image',
            'popup_link',
            'popup_destination_type',
            'popup_nationality_ids',
        ];

        $data = [];
        foreach ($fields as $field) {
            $data[$field] = $settings->getSetting($field);
        }

        return array_merge($data, AutomationSettings::all());
    }

    private function validateSectionRequest(Request $request, string $section): void
    {
        if ($section === 'announcements') {
            $request->validate([
                'section' => 'required|in:announcements',
                'popup_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'popup_destination_type' => 'required|in:general,nationalities',
                'popup_link' => 'nullable|url|max:2048',
                'popup_nationality_ids' => 'nullable|required_if:popup_destination_type,nationalities|array|min:1',
                'popup_nationality_ids.*' => 'integer|distinct|exists:nationalities,id',
            ]);

            return;
        }

        $request->validate([
            'section' => 'required|in:operations',
            'warranty_period_days' => 'required|integer|min:1|max:3650',
            'order_expiration_hours' => 'required|integer|min:1|max:720',
            'order_new_deadline_hours' => 'required|integer|min:1|max:720',
            'order_under_process_deadline_hours' => 'required|integer|min:1|max:720',
            'order_urgent_threshold_hours' => 'required|integer|min:1|max:720',
            'old_pending_orders_days' => 'required|integer|min:1|max:3650',
            'warranty_status_update_time' => 'required|date_format:H:i',
            'warranty_initialize_interval_hours' => 'required|integer|min:1|max:24',
            'orders_timer_interval_minutes' => 'required|integer|min:1|max:60',
            'orders_expired_handler_interval_minutes' => 'required|integer|min:1|max:60',
            'orders_auto_cancel_interval_minutes' => 'required|integer|min:1|max:60',
            'bank_transfer_data' => 'nullable|string',
            'sadad_transfer_data' => 'nullable|string',
        ]);
    }

    private function storeOperationsSettings(Request $request, NewSettings $settings): void
    {
        foreach (['bank_transfer_data', 'sadad_transfer_data'] as $field) {
            if ($request->has($field)) {
                $settings->setSetting($field, $request->$field);
            }
        }

        AutomationSettings::persist($request->only(AutomationSettings::keys()));
    }

    private function storeAnnouncementsSettings(Request $request, NewSettings $settings): void
    {
        $show_popup = $request->has('show_popup') ? '1' : '0';
        $settings->setSetting('show_popup', $show_popup);

        $destinationType = (string) $request->input('popup_destination_type', 'general');
        $nationalityIds = $destinationType === 'nationalities'
            ? collect($request->input('popup_nationality_ids', []))->map(fn ($id) => (int) $id)->unique()->values()->all()
            : [];

        if (! $settings->setSetting('popup_destination_type', $destinationType)
            || ! $settings->setSetting('popup_nationality_ids', json_encode($nationalityIds))) {
            throw new \RuntimeException('Unable to persist the announcement destination.');
        }

        if (! $settings->setSetting('popup_link', trim((string) $request->input('popup_link', '')))) {
            throw new \RuntimeException('Unable to persist the announcement link.');
        }

        if ($request->hasFile('popup_image')) {
            $file = $request->file('popup_image');

            $old_image = $settings->getSetting('popup_image');
            $path = app(\App\Services\ManagedFileStorage::class)
                ->uploadFile($file, 'images/settings');

            if (! $settings->setSetting('popup_image', $path)) {
                deleteFile($path);

                throw new \RuntimeException('Unable to persist the uploaded announcement image.');
            }

            if (filled($old_image) && $old_image !== $path) {
                DB::afterCommit(static function () use ($old_image): void {
                    deleteFile($old_image);
                });
            }
        }
    }

    private function resolveRedirectRoute(string $section): string
    {
        return $section === 'announcements'
            ? route('dash.settings.announcements')
            : route('dash.settings');
    }

    private function refreshAutomationDrivenData(): void
    {
        foreach ([
            'contracts:initialize-warranty',
            'orders:update-expiration',
            'orders:update-timer',
        ] as $command) {
            try {
                $exitCode = Artisan::call($command);

                if ($exitCode !== 0) {
                    Log::warning('Automation refresh command returned a non-zero exit code', [
                        'command' => $command,
                        'exit_code' => $exitCode,
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::warning('Automation refresh command failed', [
                    'command' => $command,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function zohoBranches()
    {
        $admin = auth('admin')->user();

        if ($admin?->branch_id) {
            return Branch::query()
                ->whereKey($admin->branch_id)
                ->get();
        }

        return Branch::query()
            ->ordered()
            ->get();
    }

    private function resolveZohoBranch(Request $request, $branches): ?Branch
    {
        if ($branches->isEmpty()) {
            return null;
        }

        $requestedBranchId = (int) $request->input('branch_id', $request->query('branch_id', 0));
        if ($requestedBranchId > 0) {
            $requestedBranch = $branches->firstWhere('id', $requestedBranchId);

            if ($requestedBranch) {
                return $requestedBranch;
            }
        }

        return $branches->firstWhere('is_main', true) ?: $branches->first();
    }

}
