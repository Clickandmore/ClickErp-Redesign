<?php

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class CvOptionsSettingsViewTest extends TestCase
{
    public function test_sort_dropdown_displays_the_saved_value_after_reload(): void
    {
        foreach (['random', 'oldest', 'newest'] as $order) {
            $this->assertSelectedOrder($order, $order);
        }
    }

    public function test_sort_dropdown_defaults_to_random(): void
    {
        $this->assertSelectedOrder(null, 'random');
    }

    public function test_sort_dropdown_preserves_old_input_after_validation_failure(): void
    {
        app('request')->setLaravelSession(session()->driver());
        session()->flashInput(['cv_site_sort_order' => 'oldest']);

        $this->assertSelectedOrder('newest', 'oldest');
    }

    private function assertSelectedOrder(?string $savedOrder, string $expectedOrder): void
    {
        $source = file_get_contents(resource_path('views/admin/ingaz/cv-options-settings/edit.blade.php'));
        preg_match('/<select\b[^>]*id="cv_site_sort_order"[^>]*>.*?<\/select>/s', $source, $matches);
        $this->assertNotEmpty($matches);

        $compiled = app('blade.compiler')->compileString($matches[0]);
        $settings = (object) ['cv_site_sort_order' => $savedOrder];
        $__env = app('view');

        ob_start();
        try {
            eval('?>'.$compiled);
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $this->assertStringNotContainsString('@selected', $html);
        $this->assertStringNotContainsString('$settings', $html);

        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($document);
        $selected = $xpath->query('//select[@id="cv_site_sort_order"]/option[@selected]');

        $this->assertCount(1, $selected);
        $this->assertSame($expectedOrder, $selected->item(0)->getAttribute('value'));
        $options = $xpath->query('//select[@id="cv_site_sort_order"]/option');
        $this->assertSame(['عشوائي', 'من الأقدم إلى الأحدث', 'من الأحدث إلى الأقدم'], array_map(
            static fn ($option) => trim($option->textContent),
            iterator_to_array($options)
        ));
    }
}
