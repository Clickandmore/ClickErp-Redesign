<?php

namespace App\Support;

class WebsiteSettingsAccess
{
    public const SECTIONS = ['general', 'authentication', 'ordering', 'social', 'branding', 'content', 'policies', 'seo'];

    private const FIELDS = [
        'email', 'phone', 'other_phone', 'tax_number', 'map_link', 'find_us_mode', 'years',
        'show_customer_service_during_booking', 'show_public_site',
        'external_auth_mode', 'external_order_mode', 'rental_order_mode',
        'twitter', 'facebook', 'instagram', 'snapchat', 'linkedin', 'youtube', 'tiktok', 'whatsapp',
        'logo_header', 'logo_footer', 'favicon', 'about_image', 'light_image', 'experience_image',
        'cover_image', 'qr_image', 'zatch_logo', 'there_are_offer', 'note_ar', 'note_en',
        'link_rights_and_duties', 'seo_canonical_base', 'seo_sitemap_cache_minutes',
        'seo_sitemap_worker_pages', 'seo_sitemap_worker_limit', 'seo_sitemap_offer_pages',
        'website_visitor_site_id', 'tag_manager_head', 'tag_manager_footer', 'tiktok_tag',
        'snapchat_tag', 'zoho_tag', 'hotjar_tag', 'meta_pixel_tag', 'live_chat_tag',
    ];

    private const TRANSLATED_FIELDS = [
        'website_name', 'location', 'offer_text', 'about_us_sub_title', 'footer_text', 'copyright',
        'privacy', 'terms_conditions', 'about_us_app', 'rights_users', 'rights_maids',
        'return_conditions', 'seo_article_tags', 'meta_title', 'meta_keywords', 'meta_description',
    ];

    public static function isWebsiteSection(?string $section): bool
    {
        return in_array($section, self::SECTIONS, true);
    }

    public static function allowsPayload(array $payload): bool
    {
        $allowed = array_merge(self::FIELDS, ['_token', '_method']);
        foreach (config('translatable.locales', ['ar', 'en']) as $locale) {
            foreach (self::TRANSLATED_FIELDS as $field) {
                $allowed[] = $field . ':' . $locale;
            }
        }

        // Reject mixed submissions too: the shared endpoint also saves internal settings.
        return array_diff(array_keys($payload), $allowed) === [];
    }
}
