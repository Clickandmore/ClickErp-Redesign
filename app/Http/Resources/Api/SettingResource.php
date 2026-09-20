<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $seo = $this->seoPayload();
        $visitorTracking = $this->resource->visitorTrackingPayload();
        $seo['visitor_tracking'] = $visitorTracking;
        $seo['body'] = $this->resource->seoBodyCode();

        return [
			'id' => (int)$this->id,
			'website_name' => (string)$this->website_name,
			'email' => (string)$this->email,
            'phone' => (string)$this->phone,
            'other_phone' => (string)$this->other_phone,
            'whatsapp' => (string)$this->whatsapp,

            'tax_number' => (string)$this->tax_number,
            'location' => (string)$this->location,
            'map_link' => (string)$this->map_link,
            'find_us_mode' => \App\Support\FindUsSettings::mode(),

            'experience_years' => (int)$this->experience_years,
            'copyright' => (string)$this->copyright,

            'logo_header' => $this->mediaValue($this->logo_header),
            'logo_footer' => $this->mediaValue($this->logo_footer),
            'favicon' => $this->mediaValue($this->favicon),
            'cover_image' => $this->mediaValue($this->cover_image),

            'zatch_logo' => $this->mediaValue($this->zatch_logo),
            'about_image' => $this->mediaValue($this->about_image),
            'experience_image' => $this->mediaValue($this->experience_image),
            'qr_code' => $this->mediaValue($this->qr_code),
            'second_qr_code' => $this->mediaValue($this->second_qr_code),

            'facebook' => (string)$this->facebook,
            'tiktok' => (string)$this->tiktok,
            'twitter' => (string)$this->twitter,
            'instagram' => (string)$this->instagram,
            'snapchat' => (string)$this->snapchat,
            'linkedin' => (string)$this->linkedin,
            'youtube' => (string)$this->youtube,

            'about_us_sub_title' => (string)$this->about_us_sub_title,
            'footer_text' => (string)$this->footer_text,
            'note_ar' => (string)$this->note_ar,
            'note_en' => (string)$this->note_en,

            'work_times' => (string)$this->work_times,
            'offer_text' => (string)$this->offer_text,
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
            'meta_pixel_tag' => $seo['meta_pixel_tag'],
            'live_chat_tag' => $seo['live_chat_tag'],
            'visitor_tracking' => $visitorTracking,
            'visitor_tracking_code' => $visitorTracking['embed_code'],
            'body' => $seo['body'],

            'announcement_popup' => $this->announcementPopupPayload(),

            'privacy_policy' => (string)$this->privacy_policy,
            'terms_conditions' => (string)$this->terms_conditions,
            'return_conditions' => (string)$this->return_conditions,
            'rights_maids' => (string)$this->rights_maids,
            'rights_users' => (string)$this->rights_users,
            'about_us_app' => (string)$this->about_us_app,
            'show_public_site' => (bool)$this->show_public_site,
            'customer_services' => (string)$this->customer_services,
            'has_offers' => (bool)$this->has_offers,
            'show_customer_service_during_booking' => (bool)$this->show_customer_service_during_booking,
            'external_auth_mode' => \App\Support\ExternalAuthSettings::mode(),
            'uses_otp_authentication' => \App\Support\ExternalAuthSettings::usesOtpOnly(),
            'external_order_mode' => \App\Support\ExternalOrderSettings::mode(),
            'uses_simplified_ordering' => \App\Support\ExternalOrderSettings::usesSimplifiedOrdering(),
            'rental_order_mode' => \App\Support\RentalOrderSettings::mode(),
            'uses_simplified_rental_ordering' => \App\Support\RentalOrderSettings::usesSimplifiedOrdering(),
		];
	}

    /**
     * الإعلان المنبثق للموقع الخارجي.
     *
     * القيم تُدار من /dash/settings/announcements وتُخزَّن في جدول new_settings،
     * وليس في جدول settings الذي يغذّي بقية هذا الـ Resource — لذلك تُقرأ هنا مباشرةً.
     *
     * version توقيع للمسار المخزَّن لا للرابط الناتج: روابط R2 موقّعة وتتغيّر كل نصف
     * ساعة، ولو بُني التوقيع عليها لعاد الإعلان للظهور لمن أغلقه. التوقيع على المسار
     * يتغيّر فقط عند رفع صورة جديدة — وهو بالضبط السلوك المطلوب.
     */
    private function announcementPopupPayload(): array
    {
        $empty = [
            'enabled' => false,
            'image' => '',
            'link' => '',
            'destination_type' => 'general',
            'nationality_ids' => [],
            'version' => null,
        ];

        if (! \Illuminate\Support\Facades\Schema::hasTable('new_settings')) {
            return $empty;
        }

        $newSettings = new \App\Models\NewSettings();
        $storedPath = (string) $newSettings->getSetting('popup_image');

        // النافذة كلها صورة، فصورة مفقودة تعني لا إعلان مهما كان المفتاح مفعّلًا.
        if ($storedPath === '') {
            return $empty;
        }

        $version = substr(sha1($storedPath), 0, 12);

        // لا نرسل رابط R2 الموقّع ضمن استجابة الإعدادات؛ المواقع الخارجية تخزّن
        // الاستجابة مؤقتًا مدة قد تتجاوز صلاحية التوقيع، كما قد تحتفظ برابط الصورة
        // السابقة بعد استبدالها. هذا الرابط الثابت يولّد تحويلًا جديدًا إلى الملف
        // الحالي عند تحميل الصورة، فيظل صالحًا مهما طال كاش الإعدادات.
        $image = route('api.client.public.settings.announcement-image', [
            'version' => $version,
        ]);

        return [
            'enabled' => $newSettings->getSetting('show_popup') === '1',
            'image' => $image,
            'link' => $this->announcementPopupLink($newSettings),
            'destination_type' => $newSettings->getSetting('popup_destination_type') ?: 'general',
            'nationality_ids' => $this->announcementNationalityIds($newSettings),
            'version' => $version,
        ];
    }

    private function announcementPopupLink(\App\Models\NewSettings $settings): string
    {
        if ($settings->getSetting('popup_destination_type') !== 'nationalities') {
            return (string) $settings->getSetting('popup_link');
        }

        $nationalityIds = $this->announcementNationalityIds($settings);
        if ($nationalityIds === []) {
            return '';
        }

        $parameters = ['type' => 'estqdam'];
        if (count($nationalityIds) === 1) {
            $parameters['nationality_id'] = $nationalityIds[0];
        } else {
            $parameters['nationality_ids'] = implode(',', $nationalityIds);
        }

        return route('site.all-workers.index', $parameters);
    }

    private function announcementNationalityIds(\App\Models\NewSettings $settings): array
    {
        $decoded = json_decode((string) $settings->getSetting('popup_nationality_ids'), true);

        return is_array($decoded)
            ? array_values(array_unique(array_filter(array_map('intval', $decoded))))
            : [];
    }

    private function mediaValue($storedPath): string
    {
        return (string) apiFileUrl($storedPath);
    }

    private function seoPayload(): array
    {
        $locale = $this->currentLocale();

        return [
            'locale' => $locale,
            'article_tags' => $this->translatedValue($locale, 'seo_article_tags'),
            'meta_title' => $this->translatedValue($locale, 'meta_title'),
            'meta_description' => $this->translatedValue($locale, 'meta_description'),
            'meta_keywords' => $this->translatedValue($locale, 'meta_keywords'),
            'tag_manager_head' => (string) ($this->tag_manager_head ?? ''),
            'tag_manager_footer' => (string) ($this->tag_manager_footer ?? ''),
            'tiktok_tag' => (string) ($this->tiktok_tag ?? ''),
            'snapchat_tag' => (string) ($this->snapchat_tag ?? ''),
            'zoho_tag' => (string) ($this->zoho_tag ?? ''),
            'hotjar_tag' => (string) ($this->hotjar_tag ?? ''),
            'meta_pixel_tag' => (string) ($this->meta_pixel_tag ?? ''),
            'live_chat_tag' => (string) ($this->live_chat_tag ?? ''),

            // فهرسة الموقع الخارجي. canonical_base فارغًا يعني "لم يُضبط"،
            // فيرجع الموقع إلى إعداده المحلي بدل أن يبني روابط على نص فارغ.
            'canonical_base' => trim((string) ($this->seo_canonical_base ?? '')),
            'sitemap' => [
                'worker_pages' => (bool) ($this->seo_sitemap_worker_pages ?? true),
                'worker_limit' => (int) ($this->seo_sitemap_worker_limit ?? 2000),
                'offer_pages' => (bool) ($this->seo_sitemap_offer_pages ?? true),
                'cache_minutes' => (int) ($this->seo_sitemap_cache_minutes ?? 360),
            ],
        ];
    }

    private function currentLocale(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
    }

    private function translatedValue(string $locale, string $attribute): string
    {
        return (string) (optional($this->translate($locale, false))->{$attribute} ?? '');
    }
}
