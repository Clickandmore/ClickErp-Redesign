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

    protected $fillable = ['key', 'value', 'group', 'years', 'show_customer_service_during_booking', 'note_ar', 'note_en',
        'logo_header', 'document_logo', 'admin_logo', 'light_image', 'cover_image', 'admin_login_background', 'dashboard_footer_url', 'there_are_offer', 'logo_footer', 'favicon', 'about_image', 'twitter', 'facebook', 'instagram', 'snapchat', 'linkedin', 'youtube', 'tiktok', 'whatsapp', 'email', 'phone', 'other_phone', 'map_link', 'header_link_1_title', 'header_link_1_url', 'header_link_2_title', 'header_link_2_url', 'live_chat_title', 'live_chat_url', 'admin_quick_links', 'tag_manager_head', 'tag_manager_footer', 'tiktok_tag', 'snapchat_tag', 'zoho_tag', 'hotjar_tag', 'meta_pixel_tag', 'live_chat_tag', 'show_public_site', 'external_auth_mode', 'external_order_mode', 'cv_template_enabled', 'cv_template_blade', 'cv_template_filename'
        , 'qr_image', 'experience_image', 'second_qr_image', 'tax_number', 'zatch_logo'
    ];

    protected $casts = [
        'admin_quick_links' => 'array',
        'cv_template_enabled' => 'boolean',
    ];

    protected $appends = ['header_logo_url', 'footer_logo_url'];

    public function getHeaderLogoUrlAttribute()
    {
        return $this->logo_header ? asset('storage/' . $this->logo_header) : null;
    }

    public function getFooterLogoUrlAttribute()
    {
        return $this->logo_footer ? asset('storage/' . $this->logo_footer) : null;
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logExcept(['cv_template_blade'])
            ->logOnlyDirty();
    }
}
