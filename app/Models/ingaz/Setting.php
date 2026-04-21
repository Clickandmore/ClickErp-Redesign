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
        'about_us_sub_title',
        'footer_text',
        'terms_conditions',
        'work_times',
        'about_us_app',
        'return_conditions',
        'offer_text',
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

    protected $fillable = ['logo_header', 'document_logo', 'admin_logo', 'light_image', 'cover_image', 'admin_login_background', 'there_are_offer', 'logo_footer', 'favicon', 'about_image', 'twitter', 'facebook', 'instagram', 'snapchat', 'linkedin', 'youtube', 'whatsapp', 'email', 'phone', 'other_phone', 'map_link', 'header_link_1_title', 'header_link_1_url', 'header_link_2_title', 'header_link_2_url', 'live_chat_title', 'live_chat_url', 'show_public_site'
        , 'qr_image', 'experience_image', 'second_qr_image', 'tax_number', 'zatch_logo'
    ];

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
            ->logOnlyDirty();
    }
}
