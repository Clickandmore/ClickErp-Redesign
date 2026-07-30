<?php

namespace Database\Seeders;

use App\Models\MusanedSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MusanedSectionSeeder extends Seeder
{
    public function run(): void
    {
        $section = MusanedSection::first();

        if (!$section) {
            $this->command?->warn('No Musaned section exists; nothing was restored.');

            return;
        }

        $titles = [
            'ar' => [
                'allowed_period_commitment_title' => 'نسبة الالتزام ضمن المدة المسموحة',
                'workforce_arrival_title' => 'نسبة وصول العمالة',
                'complaint_handling_title' => 'نسبة معالجة الشكاوى',
                'arrival_duration_commitment_title' => 'نسبة الالتزام بمدة الوصول',
                'customer_recommendation_title' => 'توصية العملاء بالمكتب',
                'inquiry_response_title' => 'استجابة المكتب للاستفسارات',
                'recruitment_duration_title' => 'مدة الاستقدام',
                'complaint_response_title' => 'نسبة استجابة المكتب للشكاوى',
            ],
            'en' => [
                'allowed_period_commitment_title' => 'Commitment rate within allowed period',
                'workforce_arrival_title' => 'Workforce arrival rate',
                'complaint_handling_title' => 'Complaint handling rate',
                'arrival_duration_commitment_title' => 'Commitment rate to arrival duration',
                'customer_recommendation_title' => 'Customer recommendation of the office',
                'inquiry_response_title' => 'Office response to inquiries',
                'recruitment_duration_title' => 'Recruitment duration',
                'complaint_response_title' => 'Office complaint response rate',
            ],
        ];

        DB::transaction(function () use ($section, $titles) {
            foreach ($titles as $locale => $values) {
                $translation = $section->translateOrNew($locale);

                foreach ($values as $field => $value) {
                    $translation->{$field} = $value;
                }
            }

            $section->save();
        });

        $this->command?->info('Musaned metric titles restored in Arabic and English.');
    }
}
