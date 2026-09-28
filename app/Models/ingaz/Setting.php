<?php

namespace App\Models\ingaz;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use Translatable, HasFactory, LogsActivity;

    protected $guarded = [];
    protected $table = 'settings';

    public $translatedAttributes = [
        'location',
        'privacy',
        'rights_maids',
        'rights_users',
        'copyright',
        'website_name',
        'dashboard_name',
        'document_company_name',
        'document_office_name',
        'document_footer_note',
        'delivery_form_title',
        'delivery_form_default_text',
        'shelter_receipt_title',
        'shelter_receipt_subtitle',
        'shelter_receipt_default_text',
        'shelter_receipt_default_settlement_text',
        'invoice_title',
        'invoice_subtitle',
        'invoice_footer_text',
        'rental_contract_title',
        'rental_contract_intro_text',
        'rental_contract_party_one_title',
        'rental_contract_party_one_text',
        'rental_contract_party_two_title',
        'rental_contract_service_title',
        'rental_contract_sadad_label',
        'rental_contract_sadad_code',
        'about_us_sub_title',
        'footer_text',
        'terms_conditions',
        'work_times',
        'about_us_app',
        'return_conditions',
        'offer_text',
        'seo_article_tags',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];
    protected $translationForeignKey = 'setting_id';
    public $translationModel = SettingsTranslation::class;
    protected $with = ['translations'];
    // need to be tested
//    public function __construct(array $attributes = [])
//    {
//        parent::__construct($attributes);
//        // Get all fillable attributes except 'created_at'
//        $this->translatedAttributes = array_diff($this->fillable, ['setting_id','locale','created_at','updated_at']);
//    }

    protected $fillable = ['key', 'value', 'group', 'years', 'show_customer_service_during_booking', 'external_booking_employee_selection', 'note_ar', 'note_en',
        'logo_header', 'document_logo', 'admin_logo', 'light_image', 'cover_image', 'admin_login_background', 'dashboard_footer_url', 'there_are_offer', 'logo_footer', 'favicon', 'about_image', 'twitter', 'facebook', 'instagram', 'snapchat', 'linkedin', 'youtube', 'tiktok', 'whatsapp', 'email', 'phone', 'other_phone', 'map_link', 'find_us_mode', 'header_link_1_title', 'header_link_1_url', 'header_link_2_title', 'header_link_2_url', 'live_chat_title', 'live_chat_url', 'admin_quick_links', 'tag_manager_head', 'tag_manager_footer', 'tiktok_tag', 'snapchat_tag', 'zoho_tag', 'hotjar_tag', 'meta_pixel_tag', 'live_chat_tag', 'website_visitor_site_id', 'show_public_site', 'external_auth_mode', 'external_order_mode', 'rental_order_mode', 'cv_template_enabled', 'cv_template_blade', 'cv_template_filename', 'cv_template_output_type', 'cv_public_site_url'
        , 'qr_image', 'experience_image', 'second_qr_image', 'tax_number', 'zatch_logo'
        // فهرسة الموقع الخارجي: canonical وsitemap — كانت في .env عند الموقع
        , 'seo_canonical_base', 'seo_sitemap_worker_pages', 'seo_sitemap_worker_limit', 'seo_sitemap_offer_pages', 'seo_sitemap_cache_minutes'
    ];

    protected $casts = [
        'admin_quick_links' => 'array',
        'cv_template_enabled' => 'boolean',
        'rental_cv_transfer_enabled' => 'boolean',
        'maid_transaction_edit_override_enabled' => 'boolean',
        'seo_sitemap_worker_pages' => 'boolean',
        'seo_sitemap_offer_pages' => 'boolean',
    ];

    protected $appends = ['header_logo_url', 'footer_logo_url'];

    public function getHeaderLogoUrlAttribute()
    {
        return $this->logo_header ? showFile($this->logo_header) : null;
    }

    public function getFooterLogoUrlAttribute()
    {
        return $this->logo_footer ? showFile($this->logo_footer) : null;
    }

    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value, $group = 'general')
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    public function websiteVisitorSite()
    {
        return $this->belongsTo(\App\Models\WebsiteVisitorSite::class)->withTrashed();
    }

    public function visitorTrackingPayload(): array
    {
        $site = $this->websiteVisitorSite;
        $enabled = $site && !$site->trashed() && $site->is_active;
        $embedCode = $enabled ? $site->embedCode() : null;

        return [
            'enabled' => (bool) $enabled,
            'placement' => 'body',
            'site_key' => $enabled ? $site->public_key : null,
            'script_url' => $enabled
                ? route('visitor-widget.script', ['siteKey' => $site->public_key])
                : null,
            'embed_code' => $embedCode,
            'body' => $embedCode,
            'allowed_domains' => $enabled ? array_values((array) $site->allowed_domains) : [],
        ];
    }

    public function seoBodyCode(): string
    {
        $trackingCode = $this->visitorTrackingPayload()['embed_code'];

        return collect([
            $this->tag_manager_footer,
            $this->live_chat_tag,
            $trackingCode,
        ])->map(fn ($code) => trim((string) $code))
            ->filter()
            ->implode("\n");
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logExcept(['cv_template_blade'])
            ->logOnlyDirty();
    }
}
