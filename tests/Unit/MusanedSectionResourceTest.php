<?php

namespace Tests\Unit;

use App\Http\Resources\Api\MusanedSectionResource;
use App\Models\MusanedSection;
use App\Models\MusanedSectionMetric;
use App\Models\MusanedSectionMetricTranslation;
use App\Models\MusanedSectionTranslation;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MusanedSectionResourceTest extends TestCase
{
    public function test_it_returns_only_visible_metrics_in_their_configured_order(): void
    {
        app()->setLocale('en');

        $section = new MusanedSection([
            'profile_url' => 'https://example.com/profile',
        ]);
        $section->setAttribute('id', 1);
        $sectionTranslation = new MusanedSectionTranslation([
            'title' => 'Musaned',
            'subtitle' => 'Section subtitle',
            'overall_performance' => 'Gold',
        ]);
        $sectionTranslation->setAttribute('locale', 'en');
        $section->setRelation('translations', new Collection([$sectionTranslation]));
        $section->setRelation('metrics', new Collection([
            $this->metric(11, 'ratio', 'Second ratio', 91.5, true, 1),
            $this->metric(10, 'ratio', 'First ratio', 99, true, 0),
            $this->metric(12, 'ratio', 'Hidden ratio', 50, false, 2),
            $this->metric(20, 'rating', 'Service rating', 4.75, true, 0),
        ]));

        $data = (new MusanedSectionResource($section))->toArray(request());

        $this->assertSame(['First ratio', 'Second ratio'], array_column($data['ratios'], 'title'));
        $this->assertSame([99.0, 91.5], array_column($data['ratios'], 'value'));
        $this->assertSame(['Service rating'], array_column($data['star_ratings'], 'title'));
        $this->assertSame([4.75], array_column($data['star_ratings'], 'value'));
    }

    private function metric(
        int $id,
        string $type,
        string $title,
        float $value,
        bool $isVisible,
        int $sortOrder
    ): MusanedSectionMetric {
        $metric = new MusanedSectionMetric([
            'type' => $type,
            'value' => $value,
            'is_visible' => $isVisible,
            'sort_order' => $sortOrder,
        ]);
        $metric->setAttribute('id', $id);

        $translation = new MusanedSectionMetricTranslation(['title' => $title]);
        $translation->setAttribute('locale', 'en');
        $metric->setRelation('translations', new Collection([$translation]));

        return $metric;
    }
}
