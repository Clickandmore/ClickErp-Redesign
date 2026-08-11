<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\MaidLanguageCatalog;
use Tests\TestCase;

class MaidLanguageCompatibilityTest extends TestCase
{
    public function test_legacy_language_codes_keep_their_original_sequence(): void
    {
        $this->assertSame(
            ['arabic', 'english', 'amharic', 'mother'],
            array_column(MaidLanguageCatalog::LEGACY_LANGUAGES, 'code')
        );
    }

    public function test_maid_language_payload_keeps_existing_api_shape_and_order(): void
    {
        $maid = new Maid();
        $maid->languages = [
            'arabic' => 'arabic',
            'english' => 'english',
            'amharic' => 'amharic',
            'mother' => 'mother',
        ];

        $this->assertSame(
            '["arabic","english","amharic","mother"]',
            $maid->getAttributes()['languages']
        );
    }

    public function test_language_routes_use_independent_permissions(): void
    {
        $this->assertSame(
            [PermKeys::MAID_LANGUAGES_VIEW],
            AdminRoutePermissionMatrix::resolve('languages.index')
        );
        $this->assertSame(
            [PermKeys::MAID_LANGUAGES_TOGGLE_VISIBILITY],
            AdminRoutePermissionMatrix::resolve('languages.toggle-visibility')
        );
    }
}
