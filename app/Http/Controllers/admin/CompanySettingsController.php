<?php

namespace App\Http\Controllers\admin;

use App\Http\Actions\dashboard\SettingAction;
use App\Http\Requests\ingaz\SettingRequest;
use App\Models\ingaz\Setting;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Schema;
use App\Models\CompanyProfile;
use App\Services\CompanyContractMigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanySettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:' . \App\PermKeys::COMPANY_SETTINGS_VIEW . ',admin');
        $this->middleware('permission:' . \App\PermKeys::COMPANY_SETTINGS_UPDATE . ',admin')->only('update');
        $this->middleware('permission:' . \App\PermKeys::COMPANY_SETTINGS_MIGRATION_PREVIEW . ',admin')->only('previewMigration');
        $this->middleware('permission:' . \App\PermKeys::COMPANY_SETTINGS_MIGRATION_EXECUTE . ',admin')->only('migrate');
    }

    public function index()
    {
        return view('admin.company-settings.index', [
            'settings' => Setting::query()->first() ?: Setting::query()->create(),
            'companyIdentityAvailable' => Schema::hasTable('company_profiles'),
            'company' => CompanyProfile::current(),
            'companyClients' => AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_MIGRATION_PREVIEW)
                ? User::query()->orderBy('name')->get(['id', 'name', 'phone']) : collect(),
            'accounts' => Schema::hasTable('fn_accounts') ? DB::table('fn_accounts')
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('fn_accounts as child')->whereColumn('child.parent_id', 'fn_accounts.id'))
                ->orderBy('code')->get(['id', 'code', 'name']) : collect(),
            'migrationPreview' => AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_MIGRATION_PREVIEW) ? session('company_migration_preview') : null,
            'migrationHistory' => AdminGuardHasPerm(\App\PermKeys::COMPANY_SETTINGS_HISTORY_VIEW) && Schema::hasTable('company_contract_migrations')
                ? DB::table('company_contract_migrations')->latest('id')->limit(20)->get() : collect(),
        ]);
    }

    public function update(SettingRequest $request, SettingAction $action)
    {
        abort_unless(Schema::hasTable('company_profiles'), 422, 'يلزم تطبيق تحديث قاعدة البيانات أولاً.');
        $profileData = $request->validate([
            'company_name' => 'required|string|max:255', 'company_name_en' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50', 'other_phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255', 'company_address' => 'nullable|string|max:2000',
            'company_city' => 'nullable|string|max:255',
            'tax_number' => ['nullable', 'string', 'regex:/\A3[0-9]{14}\z/'],
            'company_registration_number' => ['nullable', 'string', 'regex:/\A7[0-9]{9}\z/'],
            'company_license_number' => 'nullable|string|max:100', 'company_license_expiry_date' => 'nullable|date',
            'company_representative' => 'nullable|string|max:255', 'company_account_id' => 'nullable|integer|exists:fn_accounts,id',
        ], [
            'company_registration_number.string' => 'السجل التجاري يجب أن يتكون من 10 أرقام ويبدأ بالرقم 7.',
            'company_registration_number.regex' => 'السجل التجاري يجب أن يتكون من 10 أرقام ويبدأ بالرقم 7، مثل 7001234567.',
            'tax_number.string' => 'الرقم الضريبي يجب أن يتكون من 15 رقمًا ويبدأ بالرقم 3.',
            'tax_number.regex' => 'الرقم الضريبي يجب أن يتكون من 15 رقمًا ويبدأ بالرقم 3، مثل 300123456789003.',
        ]);
        if ($request->filled('company_account_id') && DB::table('fn_accounts')->where('parent_id', $request->company_account_id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['company_account_id' => 'اختر حسابًا فرعيًا نهائيًا لتسجيل العمليات المالية.']);
        }
        $data = $request->only(['document_logo', 'tax_number', 'phone', 'other_phone', 'email', 'company_license_number']);
        $data['document_company_name:ar'] = $profileData['company_name'];
        $data['document_company_name:en'] = $profileData['company_name_en'] ?? $profileData['company_name'];
        $data['location:ar'] = $profileData['company_address'] ?? null;
        $setting = Setting::query()->first() ?: Setting::query()->create();
        DB::transaction(function () use ($setting, $action, $data, $profileData) {
            $action->updateSetting($setting->id, $data);
            CompanyProfile::query()->updateOrCreate(['setting_id' => $setting->id], [
                'name' => $profileData['company_name'], 'name_en' => $profileData['company_name_en'] ?? null,
                'phone' => $profileData['phone'], 'phone2' => $profileData['other_phone'] ?? null,
                'email' => $profileData['email'] ?? null, 'address' => $profileData['company_address'] ?? null,
                'city' => $profileData['company_city'] ?? null, 'tax_number' => $profileData['tax_number'] ?? null,
                'registration_number' => $profileData['company_registration_number'] ?? null,
                'license_number' => $profileData['company_license_number'] ?? null,
                'license_expiry_date' => $profileData['company_license_expiry_date'] ?? null,
                'representative' => $profileData['company_representative'] ?? null,
                'account_id' => $profileData['company_account_id'] ?? null,
            ]);
        });

        return jsonSuccess(['redirect' => route('company-settings.index')], null, 200);
    }

    public function previewMigration(Request $request, CompanyContractMigrationService $service)
    {
        $data = $request->validate(['source_user_ids' => 'required|array|min:1|max:100', 'source_user_ids.*' => 'required|integer|distinct|exists:users,id']);
        $company = CompanyProfile::current();
        abort_unless($company, 422, 'احفظ بيانات الشركة أولاً.');
        $request->session()->put('company_migration_preview', $service->preview($company, $data['source_user_ids']));
        return redirect()->route('company-settings.index');
    }

    public function migrate(Request $request, CompanyContractMigrationService $service)
    {
        $request->validate(['confirm_migration' => 'accepted', 'fingerprint' => 'required|string|size:64']);
        $preview = $request->session()->get('company_migration_preview');
        abort_unless($preview && hash_equals($preview['fingerprint'], $request->fingerprint), 422, 'أعد معاينة الترحيل.');
        $company = CompanyProfile::current();
        abort_unless($company && $company->id === $preview['company_profile_id'], 422);
        $id = $service->migrate($company, $preview['source_user_ids'], $preview['fingerprint'], auth('admin')->id());
        $request->session()->forget('company_migration_preview');
        return redirect()->route('company-settings.index')->with('company_migration_done', $id);
    }
}
