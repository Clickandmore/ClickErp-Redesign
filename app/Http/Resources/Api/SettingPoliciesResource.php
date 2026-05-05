<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingPoliciesResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = $this->currentLocale();

        return [
            'id' => (string) $this->id,
            'locale' => $locale,
            'link_rights_and_duties' => (string) ($this->link_rights_and_duties ?? ''),
            'privacy' => $this->translatedValue($locale, 'privacy'),
            'terms_conditions' => $this->translatedValue($locale, 'terms_conditions'),
            'about_us_app' => $this->translatedValue($locale, 'about_us_app'),
            'rights_users' => $this->translatedValue($locale, 'rights_users'),
            'rights_maids' => $this->translatedValue($locale, 'rights_maids'),
            'return_conditions' => $this->translatedValue($locale, 'return_conditions'),
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
