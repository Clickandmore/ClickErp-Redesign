<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\dashboard\SettingAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\SettingRequest;
use App\Models\ingaz\Setting;
use App\Models\WebsiteVisitorSite;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public $folderPath = "admin.ingaz.settings";
    public $postData = [
        'logo_header',
        'document_logo',
        'admin_logo',
        'tax_number',
        'zatch_logo',
        'light_image',
        'cover_image',
        'admin_login_background',
        'dashboard_footer_url',
        'import_tutorial_video_enabled',
        'import_tutorial_video_url',
        'experience_image',
        'qr_image',
        'second_qr_image',
        'logo_footer',
        'favicon',
        'twitter',
        'facebook',
        'instagram',
        'snapchat',
        'linkedin',
        'youtube',
        'tiktok',
        'whatsapp',
        'email',
        'phone',
        'other_phone',
        'map_link',
        'find_us_mode',
        'header_link_1_title',
        'header_link_1_url',
        'header_link_2_title',
        'header_link_2_url',
        'live_chat_title',
        'live_chat_url',
        'admin_quick_links',
        'tag_manager_head',
        'tag_manager_footer',
        'tiktok_tag',
        'snapchat_tag',
        'zoho_tag',
        'hotjar_tag',
        'meta_pixel_tag',
        'live_chat_tag',
        'website_visitor_site_id',
        'seo_canonical_base',
        'seo_redirect_host',
        'seo_sitemap_worker_pages',
        'seo_sitemap_worker_limit',
        'seo_sitemap_offer_pages',
        'seo_sitemap_cache_minutes',
        'there_are_offer',
        'location:ar',
        'location:en',
        'dashboard_name:ar',
        'dashboard_name:en',
        'offer_text:ar',
        'offer_text:en',
        'copyright:ar',
        'copyright:en',
        'terms_conditions:ar',
        'terms_conditions:en',
        'about_us_app:ar',
        'about_us_app:en',
        'website_name:ar',
        'website_name:en',
        'seo_article_tags:ar',
        'seo_article_tags:en',
        'meta_title:ar',
        'meta_title:en',
        'meta_description:ar',
        'meta_description:en',
        'meta_keywords:ar',
        'meta_keywords:en',
        'document_company_name:ar',
        'document_company_name:en',
        'document_office_name:ar',
        'document_office_name:en',
        'document_footer_note:ar',
        'document_footer_note:en',
        'delivery_form_title:ar',
        'delivery_form_title:en',
        'delivery_form_default_text:ar',
        'delivery_form_default_text:en',
        'shelter_receipt_title:ar',
        'shelter_receipt_title:en',
        'shelter_receipt_subtitle:ar',
        'shelter_receipt_subtitle:en',
        'shelter_receipt_default_text:ar',
        'shelter_receipt_default_text:en',
        'shelter_receipt_default_settlement_text:ar',
        'shelter_receipt_default_settlement_text:en',
        'invoice_title:ar',
        'invoice_title:en',
        'invoice_subtitle:ar',
        'invoice_subtitle:en',
        'invoice_footer_text:ar',
        'invoice_footer_text:en',
        'rental_contract_title:ar',
        'rental_contract_title:en',
        'rental_contract_intro_text:ar',
        'rental_contract_intro_text:en',
        'rental_contract_party_one_title:ar',
        'rental_contract_party_one_title:en',
        'rental_contract_party_one_text:ar',
        'rental_contract_party_one_text:en',
        'rental_contract_party_two_title:ar',
        'rental_contract_party_two_title:en',
        'rental_contract_service_title:ar',
        'rental_contract_service_title:en',
        'rental_contract_sadad_label:ar',
        'rental_contract_sadad_label:en',
        'rental_contract_sadad_code:ar',
        'rental_contract_sadad_code:en',
        'privacy:ar',
        'privacy:en',
        "about_image",
        "about_us_sub_title:ar",
        "about_us_sub_title:en",
        "footer_text:ar",
        "footer_text:en",
        "rights_maids:ar",
        "rights_maids:en",
        "rights_users:ar",
        "rights_users:en",
        "return_conditions:ar",
        "return_conditions:en",
        'years',
        'show_customer_service_during_booking',
        'note_ar',
        'note_en',
        'link_rights_and_duties',
        'show_public_site',
        'public_site_maintenance',
    ];
    public $mainRoute = "settings";

    function __construct()
    {
        // $this->middleware('permission:الإعدادات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الإعدادات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الإعدادات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الإعدادات-حذف,admin', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $objAction)
    {
        return $this->renderSection('general');
    }

    public function section(string $section)
    {
        return $this->renderSection($section);
    }

    private function renderSection(string $section)
    {
        $allowedSections = ['dashboard', 'general', 'authentication', 'ordering', 'social', 'branding', 'content', 'policies', 'seo', 'translations'];
        if (!in_array($section, $allowedSections, true)) {
            $section = 'general';
        }

        if ($section === 'translations') {
            return redirect()->route('translation-terms.index');
        }

        $settings = Setting::first();
        if (!$settings) {
            $settings = Setting::create();
        }

        $visitorSites = $section === 'seo'
            ? WebsiteVisitorSite::query()->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.settings.sections.' . $section, compact('settings', 'visitorSites'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SettingRequest $request, objAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSetting($dataInsert, $request);
        return jsonSuccess($data, null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $action->find($id)
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(SettingRequest $request, objAction $action)
    {
        $dataInsert = $request->all();
        // dd($dataInsert);
        // Get the first setting record or create one if it doesn't exist
        $setting = Setting::first();
         if (!$setting) {

            $setting = Setting::create();
        }

        $action->updateSetting($setting->id, $dataInsert);
        return jsonSuccess(null, null, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjAction $action)
    {
        $action->deleteSetting($id);
        return jsonSuccess();
    }
}
