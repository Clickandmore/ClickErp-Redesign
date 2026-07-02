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

            'experience_years' => (int)$this->experience_years,
            'copyright' => (string)$this->copyright,

            'logo_header' => (string)$this->logo_header,
            'logo_footer' => (string)$this->logo_footer,
            'favicon' => (string)$this->favicon,
            'cover_image' => (string)$this->cover_image,

            'zatch_logo' => (string)$this->zatch_logo,
            'about_image' => (string)$this->about_image,
            'experience_image' => (string)$this->experience_image,
            'qr_code' => (string)$this->qr_code,
            'second_qr_code' => (string)$this->second_qr_code,

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
            'live_chat_tag' => $seo['live_chat_tag'],

            'privacy_policy' => (string)$this->privacy_policy,
            'terms_conditions' => (string)$this->terms_conditions,
            'return_conditions' => (string)$this->return_conditions,
            'rights_maids' => (string)$this->rights_maids,
            'rights_users' => (string)$this->rights_users,
            'about_us_app' => (string)$this->about_us_app,
            'show_public_site' => (bool)$this->show_public_site,
            'customer_services' => (string)$this->customer_services,
            'has_offers' => (bool)$this->has_offers,
            'show_customer_service_during_booking' => (bool)$this->show_customer_service_during_booking
		];
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
            'live_chat_tag' => (string) ($this->live_chat_tag ?? ''),
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
