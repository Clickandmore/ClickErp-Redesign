<?php

namespace App\Http\Requests\ingaz;

use Illuminate\Foundation\Http\FormRequest;

class FindUsLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $value = trim((string) $this->input('map_embed_url'));

        if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/isu', $value, $matches)) {
            $value = $matches[2];
        }

        $this->merge([
            'map_embed_url' => html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_address' => ['required', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'map_embed_url' => [
                'required',
                'url',
                'max:5000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $parts = parse_url((string) $value);
                    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                    $host = strtolower((string) ($parts['host'] ?? ''));
                    $path = strtolower((string) ($parts['path'] ?? ''));

                    $isGoogleHost = $host === 'google.com'
                        || str_ends_with($host, '.google.com')
                        || preg_match('/(^|\.)google\.[a-z.]+$/', $host);

                    if ($scheme !== 'https' || !$isGoogleHost || !str_contains($path, '/maps/embed')) {
                        $fail(__('find_us.validation.embed_url'));
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_name' => __('find_us.branch_name'),
            'branch_address' => __('find_us.branch_address'),
            'sort_order' => __('find_us.sort_order'),
            'map_embed_url' => __('find_us.map_embed_url'),
        ];
    }
}
