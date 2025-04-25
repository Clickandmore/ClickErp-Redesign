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
                'title:ar' => 'طلب استقدام',
                'title:en' => 'Recruitment request',
            ],
            [
                'id' => 2,
                'image' => 'images/image.png',
                'title:ar' => 'ايجار عمالة',
                'title:en' => 'Labor hire',
            ],
            [
                'id' => 3,
                'image' => 'images/image.png',
                'title:ar' => 'إستلام فورى',
                'title:en' => 'Labor transfer',
            ]
        ];

        foreach ($jobTypes as $jobType) {
            JobType::updateOrCreate(['id' => $jobType['id']], $jobType);
        }
    }
}