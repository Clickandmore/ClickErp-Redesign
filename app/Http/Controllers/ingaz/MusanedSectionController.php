<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\MusanedSectionRequest;
use App\Models\MusanedSection;
use Illuminate\Support\Facades\DB;

class MusanedSectionController extends Controller
{
    public function edit()
    {
        return view('admin.ingaz.musaned_section.edit', [
            'section' => MusanedSection::first(),
        ]);
    }

    public function update(MusanedSectionRequest $request)
    {
        DB::transaction(function () use ($request) {
            $section = MusanedSection::firstOrNew();
            $translatedFields = [
                'title',
                'subtitle',
                'overall_performance',
                'allowed_period_commitment_title',
                'workforce_arrival_title',
                'complaint_handling_title',
                'arrival_duration_commitment_title',
                'customer_recommendation_title',
                'inquiry_response_title',
                'recruitment_duration_title',
                'complaint_response_title',
            ];
            $translatedInputs = collect($translatedFields)
                ->flatMap(fn (string $field) => ["$field:ar", "$field:en"])
                ->all();

            $section->fill($request->safe()->except($translatedInputs));

            foreach (config('translatable.locales') as $locale) {
                $translation = $section->translateOrNew($locale);

                foreach ($translatedFields as $field) {
                    $translation->{$field} = $request->input("$field:$locale");
                }
            }

            $section->save();
        });

        return redirect()
            ->route('musaned-section.edit')
            ->with('success', __('musaned_section.saved'));
    }
}
