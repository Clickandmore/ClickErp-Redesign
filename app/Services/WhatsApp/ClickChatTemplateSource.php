<?php

namespace App\Services\WhatsApp;

/**
 * Gives the inbox the platform's approved templates in the same shape as
 * MetaWhatsAppTemplateService, so its template screens work for both providers.
 */
class ClickChatTemplateSource
{
    public function __construct(private readonly ClickChatWhatsAppService $clickChat)
    {
    }

    public function listTemplates(bool $fresh = false): array
    {
        $templates = array_map(fn (array $template) => [
            'name' => $template['name'],
            'language' => $template['language'],
            'category' => $template['category'],
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => $template['body']]],
        ], $this->clickChat->templates($fresh));

        return ['success' => $this->clickChat->isConfigured(), 'templates' => $templates, 'response' => []];
    }

    public function approvedTemplate(string $name, string $language): ?array
    {
        return collect($this->listTemplates()['templates'])
            ->first(fn (array $template) => $template['name'] === $name && $template['language'] === $language);
    }

    public function bodyVariableCount(array $template): int
    {
        preg_match_all('/\{\{\s*(\d+)\s*\}\}/', (string) data_get($template, 'components.0.text', ''), $matches);

        return count(array_unique($matches[1]));
    }
}
