<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends MainRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $admin = auth('admin')->user();
        if (\App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [\App\PermKeys::SETTINGS_CONTROL])) {
            return true;
        }

        return \App\Support\AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [\App\PermKeys::WEBSITE_SETTINGS_CONTROL])
            && \App\Support\WebsiteSettingsAccess::allowsPayload($this->all());
    }

    /**
     * حقل رابط الخريطة يقبل كود الـ iframe كاملاً، فنستخرج منه قيمة src قبل الحفظ.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        if (!$this->has('map_link')) {
            return;
        }

        $mapLink = trim((string) $this->input('map_link'));

        if ($mapLink !== '' && preg_match('/<iframe[^>]*\ssrc\s*=\s*["\']([^"\']+)["\']/i', $mapLink, $matches)) {
            $mapLink = trim($matches[1]);
        }

        $this->merge(['map_link' => $mapLink]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
         return [
            'second_qr_image' => 'nullable',
            'experience_image' => 'nullable',
            'qr_image' => 'nullable',
            'zatch_logo' => 'nullable',
            'tax_number' => 'nullable',
             'cover_image' => 'nullable',
            'admin_login_background' => 'nullable',
            'dashboard_footer_url' => 'nullable|url|max:500',
            'logo_header' => 'nullable',
            'document_logo' => 'nullable',
            'admin_logo' => 'nullable',
            'logo_footer' => 'nullable',
            'about_image' => 'nullable',
            'favicon' => 'nullable',
            'twitter' => 'nullable',
            'facebook' => 'nullable',
            'instagram' => 'nullable',
            'snapchat' => 'nullable',
            'linkedin' => 'nullable',
            'youtube' => 'nullable',
            'tiktok' => 'nullable',
            'whatsapp' => 'nullable',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'other_phone' => 'nullable',
            'map_link' => 'nullable|string|max:2000',
            'find_us_mode' => ['nullable', 'string', Rule::in(\App\Support\FindUsSettings::modes())],
            'header_link_1_title' => 'nullable|string|max:100',
            'header_link_1_url' => 'nullable|url|max:500',
            'header_link_2_title' => 'nullable|string|max:100',
            'header_link_2_url' => 'nullable|url|max:500',
            'live_chat_title' => 'nullable|string|max:100',
            'live_chat_url' => 'nullable|url|max:500',
            'admin_quick_links_empty' => 'nullable|boolean',
            'admin_quick_links' => 'nullable|array',
            'admin_quick_links.*.title' => 'nullable|string|max:100',
            'admin_quick_links.*.url' => 'nullable|url|max:500',
            'admin_quick_links.*.icon' => 'nullable|string|max:50',
            'admin_quick_links.*.is_visible' => 'nullable|boolean',
            'tag_manager_head' => 'nullable|string',
            'tag_manager_footer' => 'nullable|string',
            'tiktok_tag' => 'nullable|string',
            'snapchat_tag' => 'nullable|string',
            'zoho_tag' => 'nullable|string',
            'hotjar_tag' => 'nullable|string',
            'meta_pixel_tag' => 'nullable|string',
            'live_chat_tag' => 'nullable|string',
            'seo_canonical_base' => 'nullable|string|max:255|url',
            'seo_sitemap_worker_pages' => 'nullable|boolean',
            'seo_sitemap_worker_limit' => 'nullable|integer|min:0|max:50000',
            'seo_sitemap_offer_pages' => 'nullable|boolean',
            'seo_sitemap_cache_minutes' => 'nullable|integer|min:1|max:10080',
            'website_visitor_site_id' => [
                'nullable',
                'integer',
                Rule::exists('website_visitor_sites', 'id')->where(function ($query) {
                    return $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
            'location:ar' => 'nullable|string',
            'location:en' => 'nullable|string',
            'dashboard_name:ar' => 'nullable|string',
            'dashboard_name:en' => 'nullable|string',
            'copyright:ar' => 'nullable',
            'copyright:en' => 'nullable',
            'website_name:ar' => 'nullable|string',
            'website_name:en' => 'nullable|string',
            'seo_article_tags:ar' => 'nullable|string',
            'seo_article_tags:en' => 'nullable|string',
            'meta_title:ar' => 'nullable|string|max:255',
            'meta_title:en' => 'nullable|string|max:255',
            'meta_description:ar' => 'nullable|string',
            'meta_description:en' => 'nullable|string',
            'meta_keywords:ar' => 'nullable|string',
            'meta_keywords:en' => 'nullable|string',
            'document_company_name:ar' => 'nullable|string',
            'document_company_name:en' => 'nullable|string',
            'document_office_name:ar' => 'nullable|string',
            'document_office_name:en' => 'nullable|string',
            'document_footer_note:ar' => 'nullable|string',
            'document_footer_note:en' => 'nullable|string',
            'delivery_form_title:ar' => 'nullable|string',
            'delivery_form_title:en' => 'nullable|string',
            'delivery_form_default_text:ar' => 'nullable|string',
            'delivery_form_default_text:en' => 'nullable|string',
            'shelter_receipt_title:ar' => 'nullable|string',
            'shelter_receipt_title:en' => 'nullable|string',
            'shelter_receipt_subtitle:ar' => 'nullable|string',
            'shelter_receipt_subtitle:en' => 'nullable|string',
            'shelter_receipt_default_text:ar' => 'nullable|string',
            'shelter_receipt_default_text:en' => 'nullable|string',
            'shelter_receipt_default_settlement_text:ar' => 'nullable|string',
            'shelter_receipt_default_settlement_text:en' => 'nullable|string',
            'invoice_title:ar' => 'nullable|string',
            'invoice_title:en' => 'nullable|string',
            'invoice_subtitle:ar' => 'nullable|string',
            'invoice_subtitle:en' => 'nullable|string',
            'invoice_footer_text:ar' => 'nullable|string',
            'invoice_footer_text:en' => 'nullable|string',
            'rental_contract_title:ar' => 'nullable|string',
            'rental_contract_title:en' => 'nullable|string',
            'rental_contract_intro_text:ar' => 'nullable|string',
            'rental_contract_intro_text:en' => 'nullable|string',
            'rental_contract_party_one_title:ar' => 'nullable|string',
            'rental_contract_party_one_title:en' => 'nullable|string',
            'rental_contract_party_one_text:ar' => 'nullable|string',
            'rental_contract_party_one_text:en' => 'nullable|string',
            'rental_contract_party_two_title:ar' => 'nullable|string',
            'rental_contract_party_two_title:en' => 'nullable|string',
            'rental_contract_service_title:ar' => 'nullable|string',
            'rental_contract_service_title:en' => 'nullable|string',
            'rental_contract_sadad_label:ar' => 'nullable|string',
            'rental_contract_sadad_label:en' => 'nullable|string',
            'rental_contract_sadad_code:ar' => 'nullable|string',
            'rental_contract_sadad_code:en' => 'nullable|string',
            'rights_maids:ar' => 'nullable|string',
            'rights_maids:en' => 'nullable|string',
            'rights_users:ar' => 'nullable|string',
             'rights_users:en' => 'nullable|string',
             'offer_text:ar' => 'nullable|string',
             'offer_text:en' => 'nullable|string',
            'privacy:ar' => 'nullable|string',
            'privacy:en' => 'nullable|string',
            'about_us_sub_title:ar' => 'nullable|string',
            'about_us_sub_title:en' => 'nullable|string',
            'footer_text:ar' => 'nullable|string',
            'footer_text:en' => 'nullable|string',
            'years' => 'nullable|numeric',
            'show_customer_service_during_booking' => 'nullable|boolean',
            'show_public_site' => 'nullable|boolean',
            'external_auth_mode' => 'nullable|in:password,otp',
            'external_order_mode' => 'nullable|in:authenticated,simplified',
            'external_booking_employee_selection' => 'sometimes|required|boolean',
            'rental_order_mode' => 'nullable|in:full,simplified',
            'note_ar' => 'nullable|string',
            'note_en' => 'nullable|string',
            'link_rights_and_duties' => 'nullable|string',
             'there_are_offer' => 'nullable',
        ];
    }
}
