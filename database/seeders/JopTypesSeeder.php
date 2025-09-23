<?php

namespace Database\Seeders;

use App\Http\Actions\ingaz\JobTypeAction;
use App\Models\ingaz\JobType;
use Illuminate\Database\Seeder;

class JopTypesSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jobTypes = [
            [
                'id' => 1,
                'image' => 'images/image.png',
                'title:ar' => ' استقدام',
                'title:en' => 'Recruitment request',
                'description:ar' => ' ايدي عاملة مميزة ومدربة بحرفية',
                'description:en' => 'A skilled and trained worker',
            ],
            [
                'id' => 2,
                'image' => 'images/image.png',
                'title:ar' => 'ايجار عمالة',
                'title:en' => 'Labor hire',
                'description:ar' => 'تجربة نقل خدمات اسهل وأفضل ',
                'description:en' => 'Experience of easy and best services',
            ],
            [
                'id' => 3,
                'image' => 'images/image.png',
                'title:ar' => 'إستلام فورى',
                'title:en' => 'Labor transfer',
                'description:ar' =>'خدمات تأجير عمالة مدربة وموثقة',
                'description:en' => 'Labor hire services',

            ]
        ];

        foreach ($jobTypes as $jobType) {
            JobType::updateOrCreate(['id' => $jobType['id']], $jobType);
        }
    }
}