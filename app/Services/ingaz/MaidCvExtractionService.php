<?php

namespace App\Services\ingaz;

use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\ingaz\Skill;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class MaidCvExtractionService
{
    public function extract(UploadedFile $file): array
    {
        $text = $this->extractText($file);
        $data = $this->parseText($text);

        return [
            'raw_text' => $text,
            'fields' => $this->resolveSelectValues($data),
            'confidence' => $this->buildConfidence($data),
            'worker_image' => $this->extractWorkerImage($file),
        ];
    }

    private function extractText(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();
        $isPdf = $extension === 'pdf' || $mime === 'application/pdf';
        $isImage = str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'tif', 'tiff'], true);

        if (($isPdf || $isImage) && !function_exists('shell_exec')) {
            throw new RuntimeException('قراءة الملف من السيرة تحتاج تفعيل shell_exec على السيرفر.');
        }

        if ($isPdf) {
            if ($this->commandExists('pdftotext')) {
                $text = $this->runCommand(['pdftotext', '-layout', '-enc', 'UTF-8', $path, '-'], true);

                if (mb_strlen($this->normalizeText($text)) > 80) {
                    return $text;
                }
            }

            if ($this->commandExists('tesseract') && extension_loaded('imagick')) {
                return $this->runTesseract($this->createOcrImagePath($file));
            }

            throw new RuntimeException('قراءة PDF تحتاج تثبيت pdftotext، أو تثبيت Tesseract مع Imagick لملفات PDF المصورة.');
        }

        if ($isImage) {
            if (!$this->commandExists('tesseract')) {
                throw new RuntimeException('قراءة الصور تحتاج تثبيت Tesseract OCR على السيرفر.');
            }

            return $this->runTesseract($this->createOcrImagePath($file));
        }

        throw new RuntimeException('صيغة الملف غير مدعومة. استخدم PDF أو صورة JPG/PNG.');
    }

    private function parseText(string $text): array
    {
        $normalized = $this->normalizeText($text);
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $normalized))));

        return [
            'name' => $this->extractName($normalized, $lines),
            'passport_num' => $this->extractPattern($normalized, [
                '/passport\s*(?:number|no\.?|num)?\s*[:\-]?\s*([A-Z0-9\-]{5,20})/i',
                '/number\s*[:\-]?\s*([A-Z0-9\-]{5,20})/i',
                '/\b([A-Z]{1,3}\d{5,10}[A-Z]?)\b/',
            ]),
            'salary' => $this->extractNumberNear($normalized, ['salary', 'asking salary', 'monthly salary', 'الراتب']),
            'duration' => $this->extractDuration($normalized),
            'birth_date' => $this->extractDateNear($normalized, ['date of birth', 'birthday', 'birthdate', 'تاريخ الميلاد']),
            'age' => $this->extractNumberNear($normalized, ['age', 'العمر']),
            'height' => $this->extractNumberNear($normalized, ['height', 'الطول']),
            'weight' => $this->extractNumberNear($normalized, ['weight', 'الوزن']),
            'childrens_count' => $this->extractNumberNear($normalized, ['children', 'no. of kids', 'no of kids', 'عدد الاطفال', 'عدد الأولاد']),
            'nationality_text' => $this->extractTextNear($normalized, ['nationality', 'الجنسية']),
            'religion_text' => $this->extractTextNear($normalized, ['religion', 'الديانة']),
            'marital_status' => $this->extractMaritalStatus($normalized),
            'job_text' => $this->extractTextNear($normalized, ['position', 'job', 'الوظيفة']),
            'education_text' => $this->extractTextNear($normalized, ['education', 'المستوى التعليمي', 'مستوى التعليم']),
            'skills_text' => $normalized,
            'languages' => $this->extractLanguages($normalized),
            'language_percentages' => $this->extractLanguagePercentages($normalized),
            'experience_status' => $this->extractExperienceStatus($normalized),
            'additional_notes' => $this->extractRemarks($normalized),
        ];
    }

    private function resolveSelectValues(array $data): array
    {
        $fields = [
            'name:en' => $data['name'],
            'passport_num' => $data['passport_num'],
            'salary' => $data['salary'],
            'duration' => $data['duration'],
            'age' => $data['age'],
            'height' => $data['height'],
            'weight' => $data['weight'],
            'childrens_count' => $data['childrens_count'],
            'experience_status' => $data['experience_status'],
            'additional_notes' => $data['additional_notes'],
        ];

        $fields['birth_date'] = $data['birth_date'] ?: $this->birthDateFromAge($data['age']);

        if ($data['marital_status']) {
            $fields['marital_status:ar'] = $data['marital_status'];
            $fields['marital_status:en'] = in_array($data['marital_status'], ['متزوجة', 'متزوج'], true) ? 'married' : 'single';
        }

        $nationalities = Nationality::query()->get();
        $nationality = $this->matchNationality($nationalities, $data['nationality_text'], $data['skills_text']);
        if ($nationality) {
            $fields['nationality_id'] = (string) $nationality->id;
        }

        $religion = $this->matchTranslatedModel(Religion::query()->get(), $data['religion_text']);
        if ($religion) {
            $fields['religion_id'] = (string) $religion->id;
        }

        $jobTitles = JobTitle::query()->get();
        $job = $this->matchDefaultDomesticJob($jobTitles, $data['skills_text'])
            ?: $this->matchTranslatedModel($jobTitles, $data['job_text']);
        if ($job) {
            $fields['job_id'] = [(string) $job->id];
        }

        $skills = $this->matchManyTranslatedModels(Skill::query()->get(), $data['skills_text']);
        if ($skills) {
            $fields['skills'] = $skills;
        }

        if (!empty($data['languages'])) {
            $fields['languages'] = $data['languages'];
        }

        if (!empty($data['language_percentages'])) {
            $fields['percentage'] = $data['language_percentages'];
        }

        return array_filter($fields, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    private function buildConfidence(array $data): array
    {
        $keys = ['name', 'passport_num', 'salary', 'birth_date', 'age', 'nationality_text', 'religion_text'];
        $found = 0;

        foreach ($keys as $key) {
            if (!empty($data[$key])) {
                $found++;
            }
        }

        return [
            'score' => round($found / count($keys), 2),
            'found_fields' => $found,
            'total_fields' => count($keys),
        ];
    }

    private function extractName(string $text, array $lines): ?string
    {
        $patterns = [
            '/full\s*name\s*[:\-]?\s*([A-Z][A-Z\s]{3,80}?)(?=\s+(?:contact|number|passport|الاسم|رقم)|$)/i',
            '/name\s*[:\-]?\s*([A-Z][A-Z\s]{5,80}?)(?=\s+(?:contact|number|passport|الاسم|رقم)|$)/i',
            '/first\s*name\s+middle\s*name.*?\R?\s*([A-Z]{2,}(?:\s+[A-Z]{2,}){0,4})/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return $this->cleanName($matches[1]);
            }
        }

        foreach ($lines as $index => $line) {
            $lower = strtolower($line);

            if (Str::contains($lower, ['full name', 'first name', 'middle name']) && isset($lines[$index + 1])) {
                $candidate = $this->cleanName($lines[$index + 1]);
                if ($this->looksLikePersonName($candidate)) {
                    return $candidate;
                }
            }

            if (Str::contains($lower, 'full name')) {
                $candidate = $this->cleanName(preg_replace('/.*full\s*name/i', '', $line));
                if ($this->looksLikePersonName($candidate)) {
                    return $candidate;
                }
            }

            if (preg_match('/^[A-Z][A-Z\s]{8,70}$/', $line)
                && $this->looksLikePersonName($line)
                && !Str::contains($lower, ['passport', 'nationality', 'salary', 'contact', 'details', 'education', 'mutah'])) {
                return $this->cleanName($line);
            }
        }

        return null;
    }

    private function extractPattern(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return strtoupper(trim($matches[1]));
            }
        }

        return null;
    }

    private function extractNumberNear(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            if (preg_match('/' . $quoted . '[^0-9\r\n]{0,35}([0-9]+(?:\.[0-9]+)?)/iu', $text, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function extractTextNear(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            if (preg_match('/' . $quoted . '\s*[:\-]?\s*([^\r\n|:]{2,80})/iu', $text, $matches)) {
                $value = preg_replace('/\s{2,}.*/', '', trim((string) $matches[1]));
                $value = preg_replace('/\b(?:الاسم|رقم|details?|data)\b.*$/iu', '', (string) $value);

                return trim((string) $value);
            }
        }

        return null;
    }

    private function extractDuration(string $text): ?string
    {
        if (preg_match('/(?:contract\s*(?:period|مدة)?|مدة\s*العقد)\s*[:\-]?\s*([0-9]+)\s*(years?|yrs?|سنة|سنوات|شهر|months?)/iu', $text, $matches)) {
            $unit = mb_strtolower($matches[2]);
            return Str::contains($unit, ['year', 'yr', 'سنة', 'سنوات']) ? ((int) $matches[1] * 12) . ' شهر' : (int) $matches[1] . ' شهر';
        }

        return null;
    }

    private function extractDateNear(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            if (preg_match('/' . $quoted . '\s*[:\-]?\s*([0-9]{1,2}\s+[A-Z]{3,9}\s+[0-9]{4}|[A-Z]{3,9}\s+[0-9]{1,2},?\s+[0-9]{4}|[0-9]{4}[\-\/][0-9]{1,2}[\-\/][0-9]{1,2})/iu', $text, $matches)) {
                return $this->normalizeDate($matches[1]);
            }
        }

        return null;
    }

    private function extractMaritalStatus(string $text): ?string
    {
        $lower = mb_strtolower($text);

        if (Str::contains($lower, ['married', 'متزوجة', 'متزوج'])) {
            return 'متزوجة';
        }

        if (Str::contains($lower, ['single', 'عازبة', 'اعزب', 'أعزب'])) {
            return 'عازبة';
        }

        return null;
    }

    private function extractLanguages(string $text): array
    {
        $languages = [];
        $lower = mb_strtolower($text);
        $percentages = $this->extractLanguagePercentages($text);

        if (Str::contains($lower, ['arabic', 'العربية', 'العربي']) && (($percentages['arabic'] ?? 50) > 0)) {
            $languages['arabic'] = 'arabic';
        }

        if (Str::contains($lower, ['english', 'الانجليزية', 'الإنجليزية']) && (($percentages['english'] ?? 50) > 0)) {
            $languages['english'] = 'english';
        }

        return $languages;
    }

    private function extractLanguagePercentages(string $text): array
    {
        $percentages = [];
        $languageLabels = [
            'arabic' => ['arabic', 'عربي', 'العربية', 'العربي'],
            'english' => ['english', 'انجليزي', 'الانجليزية', 'الإنجليزية'],
            'amharic' => ['amharic', 'amhari', 'امهرية', 'الأمهرية'],
            'mother' => ['mother', 'native', 'mother tongue', 'اللغة الأم'],
        ];

        foreach ($languageLabels as $language => $labels) {
            foreach ($labels as $label) {
                $quoted = preg_quote($label, '/');
                if (preg_match('/' . $quoted . '[^\r\n]{0,35}\b(NO|N\/A|NONE|FAIR|GOOD|VERY\s+GOOD|EXCELLENT|FLUENT|WILLING|مقبول|جيد\s*جدا|جيد|ممتاز|لا|نعم)\b/iu', $text, $matches)) {
                    $percentage = $this->languageRatingToPercentage($matches[1]);
                    if ($percentage !== null) {
                        $percentages[$language] = $percentage;
                        break;
                    }
                }
            }
        }

        return $percentages;
    }

    private function languageRatingToPercentage(string $rating): ?int
    {
        $rating = $this->normalizeForMatch($rating);

        return match (true) {
            Str::contains($rating, ['no', 'none', 'n a', 'لا']) => 0,
            Str::contains($rating, ['excellent', 'fluent', 'ممتاز']) => 95,
            Str::contains($rating, ['very good', 'جيد جدا']) => 85,
            Str::contains($rating, ['good', 'جيد', 'نعم']) => 75,
            Str::contains($rating, ['fair', 'مقبول']) => 50,
            Str::contains($rating, ['willing']) => 35,
            default => null,
        };
    }

    private function extractExperienceStatus(string $text): string
    {
        $lower = mb_strtolower($text);

        if (Str::contains($lower, ['previous employment', 'work experience', 'country of employment'])
            && !Str::contains($lower, ['none', 'no previous'])) {
            return 'has_experience';
        }

        return 'no_experience';
    }

    private function extractRemarks(string $text): ?string
    {
        if (preg_match('/remarks?\s*[:\-]?\s*(.{10,250})/isu', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function normalizeDate(string $value): ?string
    {
        try {
            return Carbon::parse(str_replace(',', '', trim($value)))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function birthDateFromAge(?string $age): ?string
    {
        $age = (int) $age;

        if ($age < 18 || $age > 90) {
            return null;
        }

        return Carbon::today()->subYears($age)->format('Y-m-d');
    }

    private function matchTranslatedModel($models, ?string $needle)
    {
        $needle = $this->normalizeForMatch((string) $needle);

        if ($needle === '') {
            return null;
        }

        foreach ($models as $model) {
            foreach ($this->modelNames($model) as $name) {
                $normalizedName = $this->normalizeForMatch($name);
                if ($normalizedName !== '' && (Str::contains($needle, $normalizedName) || Str::contains($normalizedName, $needle))) {
                    return $model;
                }
            }
        }

        return null;
    }

    private function matchNationality($models, ?string $needle, string $fullText)
    {
        $match = $this->matchTranslatedModel($models, $needle);

        if ($match) {
            return $match;
        }

        $haystack = $this->normalizeForMatch(trim((string) $needle) . ' ' . $fullText);
        $aliases = [
            ['filipino', 'philippines', 'philippine', 'فلبين', 'فلبيني', 'فلبينية'],
            ['burundi', 'burundian', 'بورندي', 'بوروند', 'بروندي'],
            ['uganda', 'ugandan', 'اوغندا', 'أوغندا', 'اوغندي'],
            ['kenya', 'kenyan', 'كينيا', 'كيني'],
            ['ethiopia', 'ethiopian', 'اثيوبيا', 'إثيوبيا', 'حبشي'],
            ['ghana', 'ghanaian', 'غانا'],
            ['indonesia', 'indonesian', 'اندونيسيا', 'إندونيسيا'],
            ['india', 'indian', 'الهند', 'هندي'],
            ['sri lanka', 'srilanka', 'sri lankan', 'سريلانكا'],
            ['bangladesh', 'bangladeshi', 'بنجلاديش', 'بنغلاديش'],
            ['nepal', 'nepali', 'نيبال'],
        ];

        foreach ($aliases as $group) {
            $groupMatched = false;

            foreach ($group as $alias) {
                if (Str::contains($haystack, $this->normalizeForMatch($alias))) {
                    $groupMatched = true;
                    break;
                }
            }

            if (!$groupMatched) {
                continue;
            }

            foreach ($group as $alias) {
                $match = $this->matchTranslatedModel($models, $alias);
                if ($match) {
                    return $match;
                }
            }
        }

        return $this->matchClosestTranslatedModel($models, (string) $needle);
    }

    private function matchClosestTranslatedModel($models, string $needle)
    {
        $needle = $this->normalizeForMatch($needle);

        if (mb_strlen($needle) < 4) {
            return null;
        }

        $bestModel = null;
        $bestScore = 0;

        foreach ($models as $model) {
            foreach ($this->modelNames($model) as $name) {
                $normalizedName = $this->normalizeForMatch($name);

                if (mb_strlen($normalizedName) < 4) {
                    continue;
                }

                similar_text($needle, $normalizedName, $score);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestModel = $model;
                }
            }
        }

        return $bestScore >= 72 ? $bestModel : null;
    }

    private function matchManyTranslatedModels($models, string $text): array
    {
        $normalizedText = $this->normalizeForMatch($text);
        $matched = [];

        foreach ($models as $model) {
            foreach ($this->modelNames($model) as $name) {
                $normalizedName = $this->normalizeForMatch($name);
                if ($normalizedName !== '' && Str::contains($normalizedText, $normalizedName)) {
                    $matched[] = (string) $model->id;
                    break;
                }
            }
        }

        return array_values(array_unique($matched));
    }

    private function matchDefaultDomesticJob($models, string $text)
    {
        $normalizedText = $this->normalizeForMatch($text);
        $isDriver = Str::contains($normalizedText, ['driver', 'سائق', 'قياده', 'قيادة']);
        $aliases = $isDriver
            ? ['سائق خاص', 'driver', 'سائق']
            : ['عامله منزليه', 'عاملة منزلية', 'domestic helper', 'housemaid', 'house maid', 'helper'];

        foreach ($aliases as $alias) {
            $matched = $this->matchTranslatedModel($models, $alias);
            if ($matched) {
                return $matched;
            }
        }

        return null;
    }

    private function modelNames($model): array
    {
        $names = [
            $model->title ?? null,
            $model->sub_title ?? null,
        ];

        foreach ($model->translations ?? [] as $translation) {
            $names[] = $translation->title ?? null;
            $names[] = $translation->sub_title ?? null;
        }

        return array_filter(array_map('trim', $names));
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\t", "\r"], ' ', $text);
        $text = preg_replace('/[ ]{2,}/', ' ', $text);

        return trim((string) $text);
    }

    private function normalizeForMatch(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);
        $value = preg_replace('/[^a-z0-9\p{Arabic}]+/u', ' ', $value);

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function cleanName(string $value): string
    {
        $value = preg_replace('/[^A-Z\s]/i', ' ', $value);
        $value = preg_replace('/\s+/', ' ', trim((string) $value));

        return strtoupper($value);
    }

    private function looksLikePersonName(string $value): bool
    {
        $name = $this->cleanName($value);
        $parts = array_values(array_filter(explode(' ', $name), fn ($part) => mb_strlen($part) >= 2));

        if (count($parts) < 2 || count($parts) > 5) {
            return false;
        }

        $blocked = ['FULL', 'NAME', 'CONTACT', 'NUMBER', 'PASSPORT', 'DETAILS', 'MONTHLY', 'SALARY', 'CONTRACT', 'PERIOD', 'MUTAH'];

        foreach ($parts as $part) {
            if (in_array($part, $blocked, true)) {
                return false;
            }
        }

        return true;
    }

    private function runTesseract(string $imagePath): string
    {
        $outputs = [];

        foreach (['6', '4', '11'] as $psm) {
            $text = $this->runCommand(['tesseract', $imagePath, 'stdout', '-l', 'eng+ara', '--psm', $psm], true);
            if ($text !== '') {
                $outputs[] = $text;
            }
        }

        $text = trim(implode("\n", array_unique($outputs)));

        if ($text === '') {
            throw new RuntimeException('لم يتم استخراج نص من الملف. جرّب صورة أوضح أو PDF يحتوي على نص.');
        }

        return $text;
    }

    private function createOcrImagePath(UploadedFile $file): string
    {
        $image = $this->readFirstPageImage($file);

        if (!$image) {
            $extension = strtolower($file->getClientOriginalExtension());
            $mime = (string) $file->getMimeType();

            if ($extension === 'pdf' || $mime === 'application/pdf') {
                throw new RuntimeException('تعذر تحويل PDF إلى صورة. ثبّت Ghostscript أو Poppler على السيرفر.');
            }

            return $file->getRealPath();
        }

        $image->setImageColorspace(\Imagick::COLORSPACE_GRAY);
        $image->setImageDepth(8);

        if ($image->getImageWidth() < 2000) {
            $image->resizeImage(2000, 0, \Imagick::FILTER_LANCZOS, 1);
        }

        $image->normalizeImage();
        $image->contrastImage(true);
        $image->sharpenImage(0, 1);
        $image->setImageFormat('png');

        $path = tempnam(sys_get_temp_dir(), 'maid_cv_ocr_') . '.png';
        $image->writeImage($path);
        $image->clear();
        $image->destroy();

        return $path;
    }

    private function extractWorkerImage(UploadedFile $file): ?array
    {
        if (!extension_loaded('imagick')) {
            return null;
        }

        $image = $this->readFirstPageImage($file);

        if (!$image) {
            return null;
        }

        $crop = $this->cropLikelyWorkerPhoto($image);
        $image->clear();
        $image->destroy();

        if (!$crop) {
            return null;
        }

        $crop->setImageFormat('jpeg');
        $crop->setImageCompressionQuality(88);
        $crop->thumbnailImage(700, 900, true, true);

        $blob = $crop->getImagesBlob();
        $crop->clear();
        $crop->destroy();

        return [
            'filename' => 'maid-photo-' . now()->format('YmdHis') . '.jpg',
            'mime' => 'image/jpeg',
            'data_url' => 'data:image/jpeg;base64,' . base64_encode($blob),
        ];
    }

    private function cropLikelyWorkerPhoto(\Imagick $image): ?\Imagick
    {
        $width = $image->getImageWidth();
        $height = $image->getImageHeight();

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $detected = $this->cropLargestVisualComponent($image);
        if ($detected) {
            return $detected;
        }

        $isTallCv = ($height / max($width, 1)) > 1.35;
        $candidates = $isTallCv
            ? [
                [0.00, 0.16, 0.44, 0.45],
                [0.02, 0.18, 0.40, 0.42],
                [0.00, 0.12, 0.50, 0.55],
            ]
            : [
                [0.48, 0.18, 0.47, 0.48],
                [0.02, 0.02, 0.24, 0.19],
                [0.45, 0.14, 0.52, 0.55],
            ];

        foreach ($candidates as $candidate) {
            [$xRatio, $yRatio, $wRatio, $hRatio] = $candidate;
            $crop = clone $image;
            $crop->cropImage(
                max(1, (int) round($width * $wRatio)),
                max(1, (int) round($height * $hRatio)),
                max(0, (int) round($width * $xRatio)),
                max(0, (int) round($height * $yRatio))
            );
            $crop->setImagePage(0, 0, 0, 0);

            $trimmed = $this->trimWorkerCrop($crop);
            $crop->clear();
            $crop->destroy();

            if ($trimmed && $trimmed->getImageWidth() >= 80 && $trimmed->getImageHeight() >= 120) {
                return $trimmed;
            }
        }

        return null;
    }

    private function cropLargestVisualComponent(\Imagick $image): ?\Imagick
    {
        $originalWidth = $image->getImageWidth();
        $originalHeight = $image->getImageHeight();
        $scan = clone $image;
        $scanWidth = 420;
        $scan->resizeImage($scanWidth, 0, \Imagick::FILTER_POINT, 1);
        $width = $scan->getImageWidth();
        $height = $scan->getImageHeight();
        $pixels = [];

        for ($y = 0; $y < $height; $y++) {
            $pixels[$y] = [];
            for ($x = 0; $x < $width; $x++) {
                $color = $scan->getImagePixelColor($x, $y)->getColor();
                $r = (int) ($color['r'] ?? 255);
                $g = (int) ($color['g'] ?? 255);
                $b = (int) ($color['b'] ?? 255);
                $isWhite = $r > 238 && $g > 238 && $b > 238;
                $pixels[$y][$x] = !$isWhite;
            }
        }

        $visited = array_fill(0, $height, array_fill(0, $width, false));
        $best = null;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($visited[$y][$x] || !$pixels[$y][$x]) {
                    continue;
                }

                $component = $this->scanComponent($pixels, $visited, $x, $y, $width, $height);
                $boxWidth = $component['max_x'] - $component['min_x'] + 1;
                $boxHeight = $component['max_y'] - $component['min_y'] + 1;
                $boxArea = $boxWidth * $boxHeight;
                $density = $component['count'] / max($boxArea, 1);
                $aspect = $boxWidth / max($boxHeight, 1);

                if ($boxHeight < $height * 0.12 || $boxWidth < $width * 0.08) {
                    continue;
                }

                if ($aspect < 0.18 || $aspect > 1.15 || $density < 0.08) {
                    continue;
                }

                $score = $component['count'] * min($boxHeight / max($height, 1), 0.7);

                if (!$best || $score > $best['score']) {
                    $best = $component + [
                        'score' => $score,
                        'box_width' => $boxWidth,
                        'box_height' => $boxHeight,
                    ];
                }
            }
        }

        $scan->clear();
        $scan->destroy();

        if (!$best) {
            return null;
        }

        $scaleX = $originalWidth / max($width, 1);
        $scaleY = $originalHeight / max($height, 1);
        $padX = max(20, (int) round($best['box_width'] * $scaleX * 0.18));
        $padY = max(25, (int) round($best['box_height'] * $scaleY * 0.08));
        $x = max(0, (int) floor($best['min_x'] * $scaleX) - $padX);
        $y = max(0, (int) floor($best['min_y'] * $scaleY) - $padY);
        $right = min($originalWidth, (int) ceil(($best['max_x'] + 1) * $scaleX) + $padX);
        $bottom = min($originalHeight, (int) ceil(($best['max_y'] + 1) * $scaleY) + $padY);

        $crop = clone $image;
        $crop->cropImage(max(1, $right - $x), max(1, $bottom - $y), $x, $y);
        $crop->setImagePage(0, 0, 0, 0);

        return $crop;
    }

    private function scanComponent(array $pixels, array &$visited, int $startX, int $startY, int $width, int $height): array
    {
        $queue = [[$startX, $startY]];
        $visited[$startY][$startX] = true;
        $component = [
            'min_x' => $startX,
            'max_x' => $startX,
            'min_y' => $startY,
            'max_y' => $startY,
            'count' => 0,
        ];

        while ($queue) {
            [$x, $y] = array_pop($queue);
            $component['count']++;
            $component['min_x'] = min($component['min_x'], $x);
            $component['max_x'] = max($component['max_x'], $x);
            $component['min_y'] = min($component['min_y'], $y);
            $component['max_y'] = max($component['max_y'], $y);

            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$dx, $dy]) {
                $nx = $x + $dx;
                $ny = $y + $dy;

                if ($nx < 0 || $ny < 0 || $nx >= $width || $ny >= $height || $visited[$ny][$nx] || !$pixels[$ny][$nx]) {
                    continue;
                }

                $visited[$ny][$nx] = true;
                $queue[] = [$nx, $ny];
            }
        }

        return $component;
    }

    private function trimWorkerCrop(\Imagick $crop): ?\Imagick
    {
        $image = clone $crop;
        $image->setImageBackgroundColor('white');

        try {
            $image->trimImage(9000);
            $image->setImagePage(0, 0, 0, 0);
        } catch (\Throwable) {
            return $image;
        }

        $padding = max(12, (int) round(min($image->getImageWidth(), $image->getImageHeight()) * 0.04));
        $image->borderImage('white', $padding, $padding);

        return $image;
    }

    private function readFirstPageImage(UploadedFile $file): ?\Imagick
    {
        if (!extension_loaded('imagick')) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();
        $image = new \Imagick();

        try {
            if ($extension === 'pdf' || $mime === 'application/pdf') {
                $pdfImagePath = $this->renderPdfFirstPage($path);

                if (!$pdfImagePath) {
                    return null;
                }

                $image->readImage($pdfImagePath);
            } else {
                $image->readImage($path);
            }
        } catch (\Throwable) {
            $image->clear();
            $image->destroy();

            return null;
        }

        if ($image->getNumberImages() > 1) {
            $image->setIteratorIndex(0);
        }

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        }

        $image = $image->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
        $image->setImageBackgroundColor('white');
        $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_REMOVE);
        $image->setImageFormat('png');

        return $image;
    }

    private function renderPdfFirstPage(string $path): ?string
    {
        if ($this->commandExists('pdftoppm')) {
            $prefix = tempnam(sys_get_temp_dir(), 'maid_cv_pdf_');
            $outputPath = $prefix . '.png';
            @unlink($prefix);

            $this->runCommand(['pdftoppm', '-f', '1', '-singlefile', '-r', '220', '-png', $path, $prefix], true);

            return is_file($outputPath) ? $outputPath : null;
        }

        if ($this->commandExists('gs')) {
            $image = new \Imagick();
            $image->setResolution(220, 220);
            $image->readImage($path . '[0]');
            $image->setImageFormat('png');

            $outputPath = tempnam(sys_get_temp_dir(), 'maid_cv_pdf_') . '.png';
            $image->writeImage($outputPath);
            $image->clear();
            $image->destroy();

            return $outputPath;
        }

        return null;
    }

    private function commandExists(string $command): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        $result = trim((string) \shell_exec('command -v ' . \escapeshellarg($command) . ' 2>/dev/null'));

        return $result !== '';
    }

    private function runCommand(array $parts, bool $allowEmpty = false): string
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('قراءة الملف من السيرة تحتاج تفعيل shell_exec على السيرفر.');
        }

        $command = implode(' ', array_map('\escapeshellarg', $parts)) . ' 2>&1';
        $output = \shell_exec($command);
        $output = trim((string) $output);

        if ($output === '' && !$allowEmpty) {
            throw new RuntimeException('لم يتم استخراج نص من الملف. جرّب صورة أوضح أو PDF يحتوي على نص.');
        }

        return $output;
    }
}
