<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Setting;
use App\Models\ingaz\Skill;
use Illuminate\Support\Facades\Storage;

class MaidCvSvgGenerator
{
    public function render(Maid $maid, Setting $settings): string
    {
        $maid->loadMissing([
            'nationality',
            'religion',
            'jobTypes',
            'jobTitles',
            'expertises.country',
            'expertises.jobTitle',
            'office',
            'recruitmentOffice',
        ]);

        $dash = '—';
        $name = $this->e($maid->name ?: $dash);
        $passport = $this->e($maid->passport_num ?: $dash);
        $code = $this->e($maid->cv_code ?: ('CV-' . str_pad((string) $maid->id, 5, '0', STR_PAD_LEFT)));
        $job = $this->e($maid->jobTitles->first()?->title ?: $maid->jobTypes->first()?->title ?: 'عاملة منزلية');
        $nationality = $this->e($maid->nationality?->sub_title ?: $maid->nationality?->title ?: $dash);
        $religion = $this->e($maid->religion?->title ?: $dash);
        $marital = $this->e($maid->marital_status ?: $dash);
        $education = $this->e($maid->education ?: $dash);
        $age = $this->e($maid->age ?: $dash);
        $height = $this->e($maid->height ? $maid->height . ' سم' : $dash);
        $weight = $this->e($maid->weight ? $maid->weight . ' كجم' : $dash);
        $children = $this->e($maid->childrens_count ?? $dash);
        $cooking = $this->e($maid->cooking_experience ?: $dash);
        $salary = $this->money($maid->salary, $dash);
        $price = $this->money($maid->price, $dash);
        $duration = $this->e($maid->duration ? $maid->duration . ' شهر' : $dash);

        $office = $maid->office ?: $maid->recruitmentOffice;
        $officeName = $this->e($office?->name ?: $settings->title ?: 'شركة توسط للاستقدام');
        $officePhone = $this->e($office?->phone ?: $settings->phone ?: $dash);
        $officeAddress = $this->e($office?->address ?: $settings->address ?: $dash);

        $photo = $this->dataUri($maid->image);
        $logo = $this->dataUri($settings->document_logo ?: $settings->logo_header ?: $settings->logo);
        $photoMarkup = $photo
            ? '<image href="' . $this->e($photo) . '" x="680" y="58" width="155" height="188" preserveAspectRatio="xMidYMin slice"/>'
            : '<rect x="680" y="58" width="155" height="188" rx="10" fill="#e9f4f1"/>'
                . '<text x="758" y="160" class="photo-fallback">' . $this->e(mb_substr($maid->name ?: 'CV', 0, 2)) . '</text>';
        $logoMarkup = $logo
            ? '<image href="' . $this->e($logo) . '" x="45" y="42" width="190" height="62" preserveAspectRatio="xMinYMid meet"/>'
            : '';

        $cards = $this->cards([
            ['الجنسية', $nationality],
            ['الديانة', $religion],
            ['الحالة الاجتماعية', $marital],
            ['العمر', $age],
            ['المؤهل التعليمي', $education],
            ['عدد الأطفال', $children],
            ['الطول', $height],
            ['الوزن', $weight],
            ['خبرة الطبخ', $cooking],
        ]);
        $skills = $this->skills($maid);
        $experiences = $this->experiences($maid, $dash);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="1273" viewBox="0 0 900 1273" direction="rtl">
<defs>
<linearGradient id="head" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#0b1b35"/><stop offset="1" stop-color="#315a99"/></linearGradient>
<style>
text{font-family:Tahoma,Arial,sans-serif;direction:rtl;unicode-bidi:plaintext}
.section{fill:#0da67b;font-size:20px;font-weight:700;text-anchor:start}.label{fill:#8a94a3;font-size:12px;text-anchor:start}
.value{fill:#18243c;font-size:16px;font-weight:700;text-anchor:start}.side-label{fill:#71808d;font-size:12px;text-anchor:start}
.side-value{fill:#17243d;font-size:14px;font-weight:700;text-anchor:start}.skill{fill:#18243c;font-size:14px;font-weight:700;text-anchor:start}
.table-head{fill:#59d6b0;font-size:14px;font-weight:700;text-anchor:start}.table-value{fill:#26344d;font-size:13px;text-anchor:start}
.photo-fallback{fill:#0da67b;font-size:32px;font-weight:700;text-anchor:middle}.empty{fill:#8a94a3;font-size:14px;text-anchor:end}
</style>
</defs>
<rect width="900" height="1273" fill="#fff"/><rect width="900" height="285" fill="url(#head)"/>
{$logoMarkup}<text x="855" y="42" fill="#fff" font-size="14" text-anchor="start">{$officeName}</text><text x="45" y="130" fill="#40d4a7" font-size="15">سيرة ذاتية</text>
<rect x="670" y="48" width="175" height="208" rx="13" fill="#fff" opacity=".18"/>{$photoMarkup}
<text x="635" y="145" fill="#fff" font-size="34" font-weight="700" direction="ltr" unicode-bidi="embed" text-anchor="end">{$name}</text>
<text x="635" y="182" fill="#becade" font-size="17" font-weight="700" text-anchor="start">{$job}</text>
<rect x="350" y="205" width="285" height="42" rx="9" fill="#0da67b" opacity=".9"/>
<text x="615" y="232" fill="#fff" font-size="13" font-weight="700" text-anchor="start">رقم السيرة {$code} | الجواز {$passport}</text>

<rect x="682" y="285" width="218" height="928" fill="#f3f7f6"/>
<text x="855" y="345" class="section">بيانات المكتب</text>
<text x="855" y="390" class="side-label">اسم المكتب</text><text x="855" y="416" class="side-value">{$officeName}</text>
<text x="855" y="465" class="side-label">رقم التواصل</text><text x="855" y="491" class="side-value">{$officePhone}</text>
<text x="855" y="540" class="side-label">العنوان</text><text x="855" y="566" class="side-value">{$officeAddress}</text>
<text x="855" y="640" class="section">بيانات السيرة</text>
<text x="855" y="685" class="side-label">رقم السيرة</text><text x="855" y="711" class="side-value">{$code}</text>
<text x="855" y="760" class="side-label">رقم الجواز</text><text x="855" y="786" class="side-value">{$passport}</text>

<text x="645" y="335" class="section">البيانات الشخصية</text>{$cards}
<text x="645" y="720" class="section">التكاليف والعقد</text>
<rect x="38" y="748" width="190" height="76" rx="10" fill="#fff" stroke="#dce6ea"/><text x="208" y="775" class="label">الراتب الشهري</text><text x="208" y="807" class="value">{$salary}</text>
<rect x="244" y="748" width="190" height="76" rx="10" fill="#fff" stroke="#dce6ea"/><text x="414" y="775" class="label">تكلفة الاستقدام</text><text x="414" y="807" class="value">{$price}</text>
<rect x="450" y="748" width="195" height="76" rx="10" fill="#fff" stroke="#dce6ea"/><text x="625" y="775" class="label">مدة العقد</text><text x="625" y="807" class="value">{$duration}</text>
<text x="645" y="870" class="section">المهارات</text>{$skills}
<text x="645" y="1010" class="section">الخبرات العملية</text>
<rect x="38" y="1032" width="607" height="40" fill="#142541"/><text x="625" y="1058" class="table-head">الدولة</text><text x="420" y="1058" class="table-head">المهنة</text><text x="205" y="1058" class="table-head">المدة</text>
{$experiences}
<rect y="1213" width="900" height="60" fill="#142541"/><text x="855" y="1250" fill="#fff" font-size="13" text-anchor="start">{$officeName}</text><text x="45" y="1250" fill="#40d4a7" font-size="13">{$code}</text>
</svg>
SVG;
    }

    private function cards(array $fields): string
    {
        $svg = '';
        foreach ($fields as $index => [$label, $value]) {
            $x = 38 + (($index % 3) * 206);
            $y = 365 + (intdiv($index, 3) * 103);
            $svg .= '<g><rect x="' . $x . '" y="' . $y . '" width="190" height="84" rx="10" fill="#fff" stroke="#dce6ea"/>'
                . '<rect x="' . ($x + 185) . '" y="' . $y . '" width="5" height="84" rx="2" fill="#0da67b"/>'
                . '<text x="' . ($x + 170) . '" y="' . ($y + 29) . '" class="label">' . $this->e($label) . '</text>'
                . '<text x="' . ($x + 170) . '" y="' . ($y + 60) . '" class="value">' . $value . '</text></g>';
        }

        return $svg;
    }

    private function skills(Maid $maid): string
    {
        $raw = is_array($maid->skills) ? $maid->skills : (json_decode((string) $maid->skills, true) ?: []);
        $ids = collect($raw)->filter()->map(fn ($id) => (int) $id)->unique();
        $names = $ids->isEmpty()
            ? collect()
            : Skill::query()->whereIn('id', $ids)->with('translations')->get()
                ->map(fn (Skill $skill) => trim((string) $skill->title))->filter()->take(6)->values();

        if ($names->isEmpty()) {
            return '<text x="645" y="910" class="empty">لا توجد مهارات مسجلة</text>';
        }

        $svg = '';
        foreach ($names as $index => $name) {
            $x = 38 + (($index % 3) * 206);
            $y = 895 + (intdiv($index, 3) * 52);
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="190" height="42" rx="8" fill="#edf8f4" stroke="#a7ddcc"/>'
                . '<text x="' . ($x + 170) . '" y="' . ($y + 27) . '" class="skill">' . $this->e($name) . '</text>'
                . '<text x="' . ($x + 20) . '" y="' . ($y + 28) . '" fill="#0da67b" font-size="18">✓</text>';
        }

        return $svg;
    }

    private function experiences(Maid $maid, string $dash): string
    {
        if ($maid->expertises->isEmpty()) {
            return '<rect x="38" y="1072" width="607" height="45" fill="#f8fbfb" stroke="#e2eaed"/>'
                . '<text x="625" y="1101" class="empty">لا توجد خبرات سابقة</text>';
        }

        $svg = '';
        foreach ($maid->expertises->take(3) as $index => $experience) {
            $y = 1100 + ($index * 40);
            $country = $this->e($experience->country?->title ?: $dash);
            $job = $this->e($experience->jobTitle?->title ?: $dash);
            $years = $this->e($experience->years ?? $experience->experience_years ?? $dash);
            $svg .= '<rect x="38" y="' . ($y - 28) . '" width="607" height="38" fill="' . ($index % 2 ? '#fff' : '#f8fbfb') . '" stroke="#e2eaed"/>'
                . '<text x="625" y="' . $y . '" class="table-value">' . $country . '</text>'
                . '<text x="420" y="' . $y . '" class="table-value">' . $job . '</text>'
                . '<text x="205" y="' . $y . '" class="table-value">' . $years . '</text>';
        }

        return $svg;
    }

    private function dataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = ltrim((string) preg_replace('#^storage/#', '', $path), '/');
        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
    }

    private function money($value, string $dash): string
    {
        return $this->e($value !== null ? number_format((float) $value) . ' ر.س' : $dash);
    }

    private function e($value): string
    {
        return htmlspecialchars(trim((string) $value), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
