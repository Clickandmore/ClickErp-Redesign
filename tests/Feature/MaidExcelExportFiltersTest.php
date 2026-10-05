<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\MaidController;
use App\Models\ingaz\Maid;
use App\Models\RecruitmentOffices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MaidExcelExportFiltersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_excel_filters_nationality_and_office_and_exports_office_name(): void
    {
        $nationalityId = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $otherNationalityId = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religionId = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $office = RecruitmentOffices::withoutEvents(fn () => RecruitmentOffices::create(['nationality_id' => $nationalityId]));
        $office->translations()->create(['locale' => 'ar', 'title' => 'مكتب اختبار التصدير']);
        $office->translations()->create(['locale' => 'en', 'title' => 'Export test office']);
        $office->refresh();
        $prefix = 'ExportFilter-' . uniqid();
        $make = fn (array $attributes) => Maid::withoutEvents(fn () => Maid::create($attributes + [
            'name' => $prefix, 'passport_num' => uniqid('EX-'),
            'nationality_id' => $nationalityId, 'religion_id' => $religionId,
            'office_id' => $office->id, 'age' => 30, 'cv_file' => '',
            'height' => '', 'weight' => '', 'status' => 'available', 'available' => true,
        ]));
        $matched = $make([]);
        $make(['nationality_id' => $otherNationalityId]);
        $make(['office_id' => null]);

        $response = app(MaidController::class)->exportExcel(Request::create('/', 'GET', [
            'keyword' => $prefix, 'nationality_id' => $nationalityId, 'office_id' => $office->id,
        ]));
        $path = $response->getFile()->getPathname();
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $this->assertSame(2, $sheet->getHighestRow());
            $this->assertEquals($matched->id, $sheet->getCell('A2')->getValue());
            $this->assertSame('المكتب الخارجي', $sheet->getCell('M1')->getValue());
            $this->assertSame($office->title, $sheet->getCell('M2')->getValue());
        } finally {
            @unlink($path);
        }

        // Clearing filters restores every matching CV, including those without an office.
        $response = app(MaidController::class)->exportExcel(Request::create('/', 'GET', ['keyword' => $prefix]));
        $path = $response->getFile()->getPathname();
        try {
            $this->assertSame(4, IOFactory::load($path)->getActiveSheet()->getHighestRow());
        } finally {
            @unlink($path);
        }
    }
}
