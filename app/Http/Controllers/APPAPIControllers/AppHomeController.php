<?php

namespace App\Http\Controllers\APPAPIControllers;

use App\Enums\auth\AdminTypes;
use App\Http\Actions\ingaz\MaidAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\JobTitleResource;
use App\Http\Resources\Api\NotificationResource;
use App\Http\Resources\Api\PackageResource;
use App\Http\Resources\Api\SettingPoliciesResource;
use App\Http\Resources\Api\UserAddressResource;
use App\Models\auth\Admin;
use App\Models\ingaz\Contact;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Notification;
use App\Models\ingaz\Package;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Setting;
use App\Models\ingaz\Slider;
use App\Models\ingaz\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppHomeController extends Controller
{
    public function sliders()
    {
        $sliders = Slider::with('translations')->get()->map(function (Slider $slider) {
            return [
                'title' => (string) $slider->title,
                'title2' => (string) strip_tags($slider->description ?? ''),
                'image' => $this->fileUrl($slider->image),
            ];
        })->values();

        return jsonSuccess($sliders, __('api.All items'));
    }

    public function jobs()
    {
        $jobs = JobTitle::with('translations')->get();

        return jsonSuccess(JobTitleResource::collection($jobs), __('api.All items'));
    }

    public function nationalities(Request $request)
    {
        $query = Nationality::with('translations')
            ->where('is_show', true)
            ->orderByTranslation('sub_title', 'asc');

        if (($request->pagination ?? 'off') === 'on') {
            $limitPerPage = max((int) ($request->limit_per_page ?? 10), 1);
            $pagination = $query->paginate($limitPerPage);
            $data = $pagination->getCollection()
                ->map(fn (Nationality $nationality) => $this->transformNationality($nationality))
                ->values();

            $json = [
                'message' => __('api.All items'),
                'code' => 200,
                'data' => $data,
            ];

            return response()->json(
                array_merge($json, collect($pagination)->except(['data', 'links'])->toArray()),
                200
            );
        }

        $nationalities = $query->get()
            ->map(fn (Nationality $nationality) => $this->transformNationality($nationality))
            ->values();

        return jsonSuccess($nationalities, __('api.All items'));
    }

    public function customers()
    {
        $customers = Admin::query()
            ->activeForOperations()
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->orderBy('id')
            ->get()
            ->map(function (Admin $customer) {
                return [
                    'id' => (int) $customer->id,
                    'referred_by' => null,
                    'provider_id' => null,
                    'user_type' => 'staff',
                    'name' => (string) ($customer->name ?? ''),
                    'email' => $customer->email,
                    'phone_verified_at' => null,
                    'verification_code' => null,
                    'email_verificiation_code' => null,
                    'firebase_token' => null,
                    'avatar' => null,
                    'avatar_original' => null,
                    'address' => null,
                    'country' => null,
                    'city' => null,
                    'region' => '',
                    'postal_code' => null,
                    'phone' => (string) ($customer->phone ?? ''),
                    'whatsapp_phone' => (string) ($customer->whatsapp_phone ?? $customer->phone ?? ''),
                    'balance' => 0,
                    'banned' => 0,
                    'referral_code' => null,
                    'customer_package_id' => null,
                    'remaining_uploads' => 0,
                    'nationality_id' => null,
                    'merge_office' => null,
                    'created_at' => optional($customer->created_at)->toJSON(),
                    'updated_at' => optional($customer->updated_at)->toJSON(),
                ];
            })
            ->values();

        return jsonSuccess($customers, __('api.All items'));
    }

    public function jobTypes()
    {
        $jobTypes = JobType::with('translations')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function (JobType $jobType) {
                return [
                    'id' => (int) $jobType->id,
                    'title' => (string) $jobType->title,
                    'desc' => (string) strip_tags($jobType->description ?? ''),
                    'image' => $this->fileUrl($jobType->image ?? null, asset('assets_v2/No-Image-Placeholder.svg')),
                ];
            })
            ->values();

        return jsonSuccess($jobTypes, __('api.All items'));
    }

    public function religions()
    {
        $religions = Religion::with('translations')
            ->orderBy('id')
            ->get()
            ->map(function (Religion $religion) {
                return [
                    'id' => (int) $religion->id,
                    'title' => (string) $religion->title,
                ];
            })
            ->values();

        return jsonSuccess($religions, __('api.All items'));
    }

    public function packages(Request $request)
    {
        $query = Package::with('translations')->visible();

        if ($request->filled('nationality_id')) {
            $query->forNationality($request->nationality_id);
        }

        return jsonSuccess(PackageResource::collection($query->ordered()->get()), __('api.All items'));
    }

    public function notifications(Request $request)
    {
        $user = $this->resolveApiUser();

        if (!$user) {
            return jsonSuccess([], __('api.All items'));
        }

        $query = Notification::with(['order', 'user'])
            ->where('user_id', $user->id)
            ->latest();

        return generalReturn($request, $query, NotificationResource::class, __('api.All items'));
    }

    public function maids(Request $request)
    {
        $query = Maid::with([
            'jobTypes',
            'jobTitles',
            'nationality',
            'religion',
            'recruitmentOffice',
            'attachments',
        ])
            ->where('is_show', true)
            ->whereNull('deleted_at');

        if ($request->filled('nationality_id')) {
            $query->where('nationality_id', $request->nationality_id);
        }

        if ($request->filled('religion_id')) {
            $query->where('religion_id', $request->religion_id);
        }

        $jobTypeIds = $this->normalizeArrayInput($request->input('job_type_id'));
        if ($jobTypeIds !== []) {
            $query->whereHas('jobTypes', function ($jobTypeQuery) use ($jobTypeIds) {
                $jobTypeQuery->whereIn('job_type_id', $jobTypeIds);
            });
        }

        $jobTitleIds = $this->normalizeArrayInput($request->input('job_title_id'));
        if ($jobTitleIds !== []) {
            $query->whereHas('jobTitles', function ($jobTitleQuery) use ($jobTitleIds) {
                $jobTitleQuery->whereIn('job_title_id', $jobTitleIds);
            });
        }

        if ($request->filled('age_from') && $request->filled('age_to')) {
            $query->whereBetween('age', [(float) $request->age_from, (float) $request->age_to]);
        } elseif ($request->filled('age_from')) {
            $query->where('age', '>=', (float) $request->age_from);
        } elseif ($request->filled('age_to')) {
            $query->where('age', '<=', (float) $request->age_to);
        }

        $query->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if (($request->pagination ?? 'off') === 'on') {
            $limitPerPage = max((int) ($request->limit_per_page ?? 10), 1);
            $maids = $query->paginate($limitPerPage);
            $data = $maids->getCollection()
                ->map(fn (Maid $maid) => $this->transformLegacyMaid($maid))
                ->values();

            $json = [
                'message' => __('api.All items'),
                'code' => 200,
                'data' => $data,
            ];

            return response()->json(
                array_merge($json, collect($maids)->except(['data', 'links'])->toArray()),
                200
            );
        }

        return jsonSuccess(
            $query->get()->map(fn (Maid $maid) => $this->transformLegacyMaid($maid))->values(),
            __('api.All items')
        );
    }

    public function maidssearch(Request $request, MaidAction $maidAction, ?string $search = null)
    {
        $this->mergeLegacySearchRequest($request, $search);

        if (!$request->filled('search') && $request->filled('search_term')) {
            $request->merge(['search' => $request->search_term]);
        }

        $query = $maidAction->getMaidsSearch($request)
            ->with([
                'jobTypes',
                'jobTitles',
                'nationality',
                'religion',
                'recruitmentOffice',
                'attachments',
            ])
            ->where('is_show', true)
            ->whereNull('deleted_at')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if (($request->pagination ?? 'off') === 'on') {
            $limitPerPage = max((int) ($request->limit_per_page ?? 10), 1);
            $maids = $query->paginate($limitPerPage);
            $data = $maids->getCollection()
                ->map(fn (Maid $maid) => $this->transformLegacyMaid($maid))
                ->values();

            $json = [
                'message' => __('api.All items'),
                'code' => 200,
                'data' => $data,
            ];

            return response()->json(
                array_merge($json, collect($maids)->except(['data', 'links'])->toArray()),
                200
            );
        }

        return jsonSuccess(
            $query->get()->map(fn (Maid $maid) => $this->transformLegacyMaid($maid))->values(),
            __('api.All items')
        );
    }

    public function address()
    {
        $addresses = UserAddress::where('user_id', auth('api')->id())
            ->latest()
            ->get();

        return jsonSuccess(UserAddressResource::collection($addresses), 'All Addresses');
    }

    public function addAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => ['required', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:500'],
            'name' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return jsonValid($validator->errors(), $validator->errors()->first());
        }

        $address = UserAddress::create([
            'user_id' => auth('api')->id(),
            'address' => $request->address,
            'title' => $request->title,
            'phone' => $request->phone,
            'name' => $request->name,
            'notes' => $request->notes,
        ]);

        return jsonSuccess(UserAddressResource::make($address));
    }

    public function editAddress(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'address' => ['required', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:500'],
            'name' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return jsonValid($validator->errors(), $validator->errors()->first());
        }

        $address = UserAddress::where('user_id', auth('api')->id())->find($id);

        if (!$address) {
            return jsonSuccess(null, 'هذا العنوان غير موجود', 201);
        }

        $address->update([
            'address' => $request->address,
            'title' => $request->title,
            'phone' => $request->phone,
            'name' => $request->name,
            'notes' => $request->notes,
        ]);

        return jsonSuccess(UserAddressResource::make($address->fresh()));
    }

    public function deleteAddress(int $id)
    {
        $address = UserAddress::where('user_id', auth('api')->id())->find($id);

        if (!$address) {
            return jsonSuccess(null, 'هذا العنوان غير موجود', 201);
        }

        $address->delete();

        return jsonSuccess(null, 'تم حذف العنوان بنجاح');
    }

    public function contactUs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            return jsonValid($validator->errors(), $validator->errors()->first());
        }

        $contact = Contact::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? '',
            'subject' => $request->subject,
            'message' => $request->message,
        ]);

        return jsonSuccess($contact, 'تم بنجاح');
    }

    public function settings()
    {
        $setting = Setting::with('translations')->first();

        if (!$setting) {
            return jsonSuccess(null, __('api.not found'), 201);
        }

        return jsonSuccess($this->transformLegacySettings($setting), __('api.All items'));
    }

    public function settingsPolicies()
    {
        $setting = Setting::with('translations')->first();

        if (!$setting) {
            return jsonSuccess(null, __('api.not found'), 201);
        }

        return jsonSuccess(SettingPoliciesResource::make($setting), __('api.All items'));
    }

    public function formatLegacyMaid(Maid $maid): array
    {
        return $this->transformLegacyMaid($maid);
    }

    private function transformNationality(Nationality $nationality): array
    {
        return [
            'id' => (int) $nationality->id,
            'logo' => $this->fileUrl($nationality->logo, asset('assets_v2/No-Image-Placeholder.svg')),
            'country' => (string) $nationality->title,
            'desc' => (string) strip_tags($nationality->description ?? ''),
            'price' => $this->normalizeNumber($nationality->price),
            'price_service' => $this->normalizeNumber($nationality->service_transportation_cost ?? $nationality->price),
        ];
    }

    private function transformLegacyMaid(Maid $maid): array
    {
        $setting = $this->settingsRecord();
        $jobTitle = $maid->jobTitles->first();
        $jobType = $maid->effective_job_type ?? $maid->jobTypes->first();
        $images = collect();

        if ($maid->image) {
            $images->push(['image' => $this->fileUrl($maid->image)]);
        }

        foreach ($maid->attachments as $attachment) {
            $url = $this->fileUrl($attachment->path);
            if ($url !== '') {
                $images->push(['image' => $url]);
            }
        }

        $images = $images
            ->unique('image')
            ->values()
            ->all();

        return [
            'id' => (int) $maid->id,
            'nationality_id' => (int) $maid->nationality_id,
            'religion_id' => (int) $maid->religion_id,
            'job_id' => (int) ($jobTitle?->id ?? 0),
            'type' => (string) ($jobType?->title ?? ''),
            'nationality' => (string) ($maid->nationality?->title ?? ''),
            'passport_num' => (string) ($maid->passport_num ?? ''),
            'religion' => (string) ($maid->religion?->title ?? ''),
            'social_type' => (string) ($maid->marital_status ?? ''),
            'age' => $this->normalizeNumber($maid->age) ?? 0,
            'experience_years' => (string) ($maid->experience_years ?? ''),
            'cv_file' => (string) ($this->fileUrl($maid->cv_file, asset('public'))),
            'image' => (string) $this->fileUrl($maid->image),
            'available' => $maid->available === null ? '' : (string) $maid->available,
            'customer_service_whatsapp' => (string) ($setting?->whatsapp ?? ''),
            'available_at' => (string) ($maid->available_at ?? ''),
            'is_show' => $maid->is_show ? 'true' : 'false',
            'images' => $images,
            'job_title' => (string) ($jobTitle?->title ?? ''),
            'salary' => $this->normalizeNumber($maid->resolved_salary ?? $maid->salary) ?? 0,
            'cv_name' => (string) ($maid->name ?? ''),
            'passport_number' => null,
            'high_degree' => '',
            'type_of_experience' => (string) ($maid->experience_status ?? ''),
            'nationalitie_price' => $this->normalizeNumber($maid->nationality?->price) ?? 0,
            'reasonService' => (string) ($maid->additional_notes ?? $maid->offer_description ?? ''),
            'periodService' => (string) ($maid->duration ?? ''),
            'nationalitie_service_price' => $this->normalizeNumber($maid->nationality?->service_transportation_cost) ?? 0,
        ];
    }

    private function transformLegacySettings(Setting $setting): array
    {
        $phone = (string) ($setting->phone ?? '');
        $otherPhone = (string) ($setting->other_phone ?? '');
        $whatsapp = (string) ($setting->whatsapp ?? '');
        $location = (string) ($setting->location ?? '');
        $websiteName = (string) ($setting->website_name ?? '');
        $seo = $this->settingsSeoPayload($setting);
        $sliderIds = Slider::query()->orderBy('id')->pluck('id')->map(fn ($id) => (string) $id)->values();

        return [
            'id' => (string) $setting->id,
            'whatsapp' => $whatsapp,
            'privacy' => (string) ($setting->privacy ?? ''),
            'terms_conditions' => (string) ($setting->terms_conditions ?? ''),
            'about_us_app' => (string) ($setting->about_us_app ?? ''),
            'title' => $websiteName,
            'footer_desc' => (string) ($setting->footer_text ?? ''),
            'seo' => $seo,
            'seo_article_tags' => $seo['article_tags'],
            'meta_title' => $seo['meta_title'],
            'meta_description' => $seo['meta_description'],
            'meta_keywords' => $seo['meta_keywords'],
            'tag_manager_head' => $seo['tag_manager_head'],
            'tag_manager_footer' => $seo['tag_manager_footer'],
            'tiktok_tag' => $seo['tiktok_tag'],
            'snapchat_tag' => $seo['snapchat_tag'],
            'zoho_tag' => $seo['zoho_tag'],
            'hotjar_tag' => $seo['hotjar_tag'],
            'live_chat_tag' => $seo['live_chat_tag'],
            'phone1' => $phone,
            'phone2' => $whatsapp !== '' ? $whatsapp : $otherPhone,
            'phone3' => $otherPhone,
            'phone4' => '',
            'whatsappNumber' => $whatsapp,
            'callNumber' => $phone,
            'address1' => $location,
            'address2' => $location,
            'latitude' => '',
            'longitude' => '',
            'email1' => (string) ($setting->email ?? ''),
            'email2' => (string) ($setting->email ?? ''),
            'our_service_desc' => '',
            'our_family_title1' => '',
            'our_family_desc1' => '',
            'our_family_image1' => '',
            'our_family_title2' => '',
            'our_family_desc2' => '',
            'our_family_image2' => '',
            'our_statistics_desc' => '',
            'recruitment_step_desc' => '',
            'recruitment_step1_desc' => '',
            'recruitment_step2_desc' => '',
            'recruitment_step3_desc' => '',
            'recruitment_step4_desc' => '',
            'recruitment_step5_desc' => '',
            'recruitment_step6_desc' => '',
            'application_for_the_recruitment' => '',
            'desc' => '',
            'terms_condition_link' => '',
            'about_us_link' => '',
            'privacy_policy_link' => '',
            'facebook' => $this->blankToNull($setting->facebook),
            'twitter' => $this->blankToNull($setting->twitter),
            'instagram' => $this->blankToNull($setting->instagram),
            'linkedin' => $this->blankToNull($setting->linkedin),
            'telegram' => '',
            'youtube' => $this->blankToNull($setting->youtube),
            'tiktok' => $this->blankToNull($setting->tiktok),
            'google_plus' => '',
            'snapchat_ghost' => $this->blankToNull($setting->snapchat) ?? '',
            'about_app' => (string) ($setting->about_us_app ?? ''),
            'terms_condition' => (string) ($setting->terms_conditions ?? ''),
            'privacy_policy' => '',
            'login_banner' => $this->fileUrl(
                $setting->admin_login_background ?? $setting->cover_image ?? $setting->light_image ?? null
            ),
            'image_slider' => $sliderIds->toJson(),
            'company_profile_pdf' => '',
            'fax' => '',
            'android_app' => '',
            'ios_app' => '',
            'link' => (string) ($setting->map_link ?? ''),
            'sms_user_name' => '',
            'sms_user_pass' => '',
            'sms_sender' => '',
            'publisher' => '',
            'default_language' => app()->getLocale() ?: 'ar',
            'default_theme' => '',
            'offer_muted' => '',
            'offer_notification' => (string) ($setting->offer_text ?? ''),
        ];
    }

    private function settingsSeoPayload(Setting $setting): array
    {
        $locale = app()->getLocale();
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
        $translation = $setting->translate($locale, false);

        return [
            'locale' => $locale,
            'article_tags' => (string) ($translation?->seo_article_tags ?? ''),
            'meta_title' => (string) ($translation?->meta_title ?? ''),
            'meta_description' => (string) ($translation?->meta_description ?? ''),
            'meta_keywords' => (string) ($translation?->meta_keywords ?? ''),
            'tag_manager_head' => (string) ($setting->tag_manager_head ?? ''),
            'tag_manager_footer' => (string) ($setting->tag_manager_footer ?? ''),
            'tiktok_tag' => (string) ($setting->tiktok_tag ?? ''),
            'snapchat_tag' => (string) ($setting->snapchat_tag ?? ''),
            'zoho_tag' => (string) ($setting->zoho_tag ?? ''),
            'hotjar_tag' => (string) ($setting->hotjar_tag ?? ''),
            'live_chat_tag' => (string) ($setting->live_chat_tag ?? ''),
        ];
    }

    private function fileUrl(?string $path, ?string $fallback = ''): string
    {
        return showFile($path) ?: (string) $fallback;
    }

    private function settingsRecord(): ?Setting
    {
        static $setting = false;

        if ($setting === false) {
            $setting = Setting::with('translations')->first();
        }

        return $setting ?: null;
    }

    private function mergeLegacySearchRequest(Request $request, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $parsed = [];
        parse_str('search=' . $search, $parsed);

        $merge = [];

        if (!$request->filled('search') && !empty($parsed['search'])) {
            $merge['search'] = $parsed['search'];
        }

        foreach (['pagination', 'limit_per_page', 'search_term'] as $key) {
            if (!$request->filled($key) && array_key_exists($key, $parsed)) {
                $merge[$key] = $parsed[$key];
            }
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    private function normalizeArrayInput($value): array
    {
        $items = is_array($value) ? $value : [$value];

        return collect($items)
            ->filter(fn($item) => $item !== null && $item !== '')
            ->map(fn($item) => is_numeric($item) ? (int) $item : $item)
            ->values()
            ->all();
    }

    private function blankToNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' || $value === '#'
            ? null
            : (string) $value;
    }

    private function normalizeNumber($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        $number = (float) $value;

        return floor($number) == $number ? (int) $number : $number;
    }

    private function resolveApiUser()
    {
        if (!request()->bearerToken()) {
            return null;
        }

        try {
            return auth('api')->user();
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
