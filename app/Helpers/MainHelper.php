<?php

use App\Helpers\Constant;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Setting;
use App\Models\ShelterWorkers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;

use function PHPSTORM_META\map;

if (!function_exists('toObject')) {
    function toObject($item)
    {
        return json_decode(json_encode($item));
    }
}

if (!function_exists('apiJsonEncodingOptions')) {
    function apiJsonEncodingOptions(): int
    {
        $options = JSON_UNESCAPED_UNICODE;

        if (!app()->environment('production')) {
            $options |= JSON_PRETTY_PRINT;
        }

        return $options;
    }
}

/**
 * Show the form for creating a new resource.
 *
 * @return \Illuminate\Http\Response
 */
if (!function_exists('jsonSuccess')) {
    function jsonSuccess($data = null, $msg = null, $code = 200)
    {
        $msg = ($msg == null) ? __("auth.done successfully") : $msg;
        return response()->json([
            // API clients (including the connected recruitment websites) use
            // this flag to distinguish a successful response from an error.
            // Keep the legacy fields below for existing dashboard consumers.
            'status' => true,
            'code' => $code,
            'data' => $data,
            'message' => $msg,
        ], 200, [], apiJsonEncodingOptions());
    }
}
if (!function_exists('submitButton')) {
    function submitButton($route = "", $text = "", $color = 'primary')
    {
        $fullText = ($text != "") ? $text : __("buttons.save");
        return '<button class="btn btn-' . $color . ' mySubmitButton" type="submit">' . $fullText . '</button>';
    }
}
if (!function_exists('closeButton')) {
    function closeButton($class = "", $text = "")
    {
        $fullText = ($text != "") ? $text : __("buttons.close");
        return '<button class="btn btn-outline-secondary upCreateModalClose ' . $class . '" type="button" data-bs-dismiss="modal">' . $fullText . '</button>';
    }
}
if (!function_exists('jsonError')) {
    function jsonError($msg = "", $code = 400)
    {
        if (!is_int($code)) {
            if (is_numeric($code)) {
                $code = (int) $code;
            } else {
                if ($msg === null || $msg === '') {
                    $msg = $code;
                }
                $code = 400;
            }
        }
        return response()->json([
            'code' => $code,
            'message' => $msg,
        ], $code, [], apiJsonEncodingOptions());
    }
}

if (!function_exists('jsonValid')) {
    function jsonValid($data, $msg = "", $code = 422)
    {
        $message = $msg;
        if (is_object($data) && method_exists($data, 'all')) {
            $errors = $data->all();
            $message = !empty($errors) ? reset($errors) : $msg;
        }

        return response()->json([
            'code' => $code,
            'data' => $data,
            'message' => $message,
        ], 422, [], apiJsonEncodingOptions());
    }
}

/**
 * Show the form for creating a new resource.
 *
 * @return \Illuminate\Http\Response
 */

if (!function_exists('addButton')) {
    function addButton($route, $text = "", $class = "btn-success")
    {
        $fullText = __("buttons.add button") . ' ' . $text;
        return '
                <div class="d-flex mb-3 justify-content-end">
                    <button type="button" class="btn ' . $class . ' float-start addButton mx-1" data-bs-toggle="modal" data-bs-target="#createModal"
                           model-title="' . $fullText . '"  model-route="' . $route . '" title="' . __("buttons.add button") . '" >
                       <i class="fa fa-plus-circle"></i>  ' . $fullText . '
                    </button>
                </div>
                ';
    }
}

if (!function_exists('editButton')) {
    function editButton($route, $text = "")
    {
        $fullText = __("buttons.edit") . ' ' . $text;
        return '<button type="button" class="btn btn-sm btn-primary btn-edit editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                       model-route="' . $route . '" model-title="' . $fullText . '" title="تعديل">
                   <i class="fa fa-pencil"></i>
                </button>';
    }
}
if (!function_exists('showButton')) {

    function showButton($route, $text = "")
    {
        $fullText = __("buttons.show") . ' ' . $text;
        return '<button type="button" class="btn btn-sm btn-warning btn-edit editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                       model-route="' . $route . '" model-title="' . $fullText . '" title="تفاصيل">
                   <i class="fa fa-eye"></i>
                </button>';
    }
}

// if (!function_exists('editButton')) {
//     function editButton($route, $text = "",$permissionName = "")
//     {
//         $editButton = '';
//         if (Auth::guard('admin')->user()->hasPermission($permissionName)) {
//             $fullText = __("buttons.edit") . ' ' . $text;
//             $editButton = '<button type="button" class="btn btn-primary editButton" data-bs-toggle="modal" data-bs-target="#createModal"
//                        model-route="' . $route . '" model-title="' . $fullText . '">
//                             <i class="fa fa-pencil"></i>
//                         </button>';
//         }
//         return $editButton;
//     }
// }

if (!function_exists('leaveButton')) {
    function leaveButton($route, $text = "")
    {
        $fullText = 'تسجيل الانصراف ' . ' ' . $text;
        return '<button type="button" class="btn btn-primary editButton" data-bs-toggle="modal" data-bs-target="#createModal"
                       model-route="' . $route . '" model-title="' . $fullText . '">
                    تسجيل الانصراف
                </button>';
    }
}

if (!function_exists('deleteButton')) {
    function deleteButton($route)
    {
        return '<button type="button" class="btn btn-sm btn-danger btn-delete deleteButton" delete-route="' . $route . '" title="حذف">
                      <i class="fa fa-trash-o"></i> ' . __('ingaz.delete') . '
                </button>';
    }
}

// function deleteButton($route, $permissionName)
// {
//     if (Auth::guard('admin')->user()->hasPermission($permissionName)) {
//         return '<button type="button" class="btn btn-danger deleteButton" delete-route="' . $route . '">
//                     <i class="fa fa-trash-o"></i>
//                 </button>';
//     } else {
//         return '';
//     }
// }

if (!function_exists('confirmButton')) {
    function confirmButton($route, $id)
    {
        return '<button type="button" class="btn btn-warning confirmButton"  confirm-id="' . $id . '" confirm-route="' . $route . '">
                      <i class="fa fa-check-square-o"></i>
                </button>';
    }
}

if (!function_exists('updateStatusButton')) {
    function updateStatusButton($table, $status, $id, $text = null)
    {
        $class = $status ? 'btn-success' : 'btn-secondary';
        if ($text == null) {
            $text = $status ? 'نشط' : 'غير نشط';
        }
        return '<button class="btn ' . $class . ' btn-sm toggle-status" data-id="' . $id . '" data-table="' . $table . '">' . $text . '</button>';
    }
}

if (!function_exists('updateSuccessEmployeeStatusButton')) {
    function updateSuccessEmployeeStatusButton($table, $id, $text)
    {
        return '<button class="btn btn-success btn-sm accept-button" data-id="' . $id . '" data-table="' . $table . '">' . $text . '</button>';
    }
}
if (!function_exists('updateDangerEmployeeStatusButton')) {
    function updateDangerEmployeeStatusButton($table, $id, $text)
    {
        return '<button class="btn btn-danger btn-sm reject-button" data-id="' . $id . '" data-table="' . $table . '">' . $text . '</button>';
    }
}

/**
 * Show the form for creating a new resource.
 *
 * @return \Illuminate\Http\Response
 */

//if (!function_exists('getEnumData')) {
//    function getEnumData($enumArr, $array_name, $optionSelect = '')
//    {
//        $options = '<option value="">' . helperTrans('ingaz.choose') . '</option>';
//        $constantArray = Constant::globalArray[$array_name];
//        $locale = \App::currentLocale();
//        foreach ($enumArr as $case) {
//            $text = (isset($constantArray[$case->value][$locale])) ? $constantArray[$case->value][$locale] : "undefined";
//            $selected = ($optionSelect == $case->value) ? "selected" : "";
//            $options .= '<option value="' . $case->value . '"  ' . $selected . '>' . $text . '</option>';
//        }
//        return $options;
//    }
//}
if (!function_exists('getEnumData')) {
    function getEnumData($enumArr, $optionSelect = '')
    {
        $options = '<option value="">' . helperTrans('auth.choose') . '</option>';
        $locale = \App::currentLocale();
        foreach ($enumArr as $case) {
            $selected = ($optionSelect == $case->value) ? "selected" : "";
            $options .= '<option value="' . $case->value . '"  ' . $selected . '>' . $case->lang() . '</option>';
        }
        return $options;
    }
}

function getSexType($value)
{
    $iconPath = $value == 1
        ? asset('site/assets/images/user2.webp') // females
        : asset('site/assets/images/user1.webp'); // males/default

    return '<img src="' . $iconPath . '" alt="gender-icon" style="height:50px;width:50px;" class="avatar rounded-circle bg-light p-1">';
}

if (!function_exists('getEnumValue')) {
    function getEnumValue($array_name, $key)
    {
        if ($array_name === 'contract_statuses') {
            return \App\Support\RecruitmentContractStageLabels::label((string) $key, \App::currentLocale())
                ?? "undefined";
        }

        if ($array_name === 'orders_statuses') {
            return \App\Support\RecruitmentOrderStatusLabels::label((string) $key, \App::currentLocale())
                ?? "undefined";
        }

        $constantArray = Constant::globalArray[$array_name];
        $locale = \App::currentLocale();
        return (isset($constantArray[$key][$locale])) ? $constantArray[$key][$locale] : "undefined";
    }
}

/*
<option value="">اختر </option>
@if ($array != null && !empty($array) && count($array) >0)
@foreach ($array as $one)
<option value="">اختر</option>
@endforeach
@endif
 */

if (!function_exists('showSelectElement')) {
    function showSelectElement($array, $key_name, $optionSelect = '', $attr = "")
    {
        $options = '<option value="">اختر </option>';
        if ($array != null && !empty($array) && count($array) > 0) {
            foreach ($array as $one) {
                $text = $one->{$key_name};
                $value = $one->id;
                $selected = ($optionSelect == $value) ? "selected" : "";
                // النص والقيمة قد يأتيان من بيانات العملاء (أسماء) — يجب تهريبهما ضد XSS.
                // $attr يمرّره المطوّر كسمات HTML ثابتة ولا يُهرَّب عمدًا.
                $options .= '<option value="' . e($value) . '"  ' . $attr . '  ' . $selected . '>' . e($text) . '</option>';
            }
        } else {
            $options = '<option value="">لا يوجد بيانات </option>';
        }

        return $options;
    }
}

if (!function_exists('uploadFile')) {

    function uploadFile($image, $path, $compress = null, $quality_ratio = 90)
    {
        $storage = app(\App\Services\ManagedFileStorage::class);
        $directory = trim((string) $path, '/');

        // رفع فشل على مستوى PHP (تجاوز upload_max_filesize مثلًا) يصل كملف "غير صالح"،
        // وأي محاولة لنقله ترمي استثناء إنجليزيًا خامًا ينتهي بخطأ 500 غامض.
        if ($image instanceof \Illuminate\Http\UploadedFile && !$image->isValid()) {
            abort(422, uploadErrorMessage($image));
        }

        if ($compress != null) {
            try {
                $img = Image::make($image);
                $contents = (string) $img->encode('webp', $quality_ratio);

                return $storage->storeFileContents($contents, $directory, 'webp');
            } catch (\Exception $e) {
                // SVG و PDF والفيديو لا يقرأها GD — تُخزَّن كما هي بدل الضغط.
                logger()->error('Error in uploadFile: ' . $e->getMessage());
            }
        }

        return $storage->uploadFile($image, $directory);
    }
}

if (!function_exists('uploadErrorMessage')) {
    function uploadErrorMessage(\Illuminate\Http\UploadedFile $file): string
    {
        $name = $file->getClientOriginalName();

        switch ($file->getError()) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم الملف "' . $name . '" أكبر من الحد المسموح به على الخادم ('
                    . ini_get('upload_max_filesize') . ').';
            case UPLOAD_ERR_PARTIAL:
                return 'لم يكتمل رفع الملف "' . $name . '"، أعد المحاولة.';
            case UPLOAD_ERR_NO_FILE:
                return 'لم يتم اختيار أي ملف.';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                return 'تعذر حفظ الملف على الخادم مؤقتًا، راجع إعدادات الخادم.';
            default:
                return 'تعذر رفع الملف "' . $name . '".';
        }
    }
}

if (!function_exists('deleteFile')) {
    function deleteFile($fileFullPath)
    {
        return app(\App\Services\ManagedFileStorage::class)->delete($fileFullPath);
    }
}

//   if (!function_exists('showFile')) {
//       function showFile($fileFullPath)
//       {
//           return asset('/storage/' . $fileFullPath);
//       }
//  }

if (!function_exists('showFile')) {
    function showFile($fileFullPath)
    {
        if (!is_string($fileFullPath)) {
            return null;
        }

        if ($fileFullPath === 'null' || $fileFullPath === '') {
            return null;
        }

        return app(\App\Services\ManagedFileStorage::class)->url($fileFullPath);
    }
}

if (!function_exists('apiFileUrl')) {
    /**
     * قيمة ملف صالحة للاستهلاك من الموقع الخارجي / التطبيق.
     *
     * ملفات R2 ترجع كرابط مطلق كامل، والملفات المحلية ترجع بمسارها النسبي كما هو
     * (الكلاينت يضيف نطاق الـ ERP بنفسه). الهدف ألا تتسرب قيمة "r2://..." الخام إلى الـ API.
     */
    function apiFileUrl($storedPath, string $fallback = '')
    {
        if (!is_string($storedPath)) {
            return $fallback;
        }

        $storedPath = trim($storedPath);

        if ($storedPath === '' || $storedPath === 'null') {
            return $fallback;
        }

        if (!app(\App\Services\ManagedFileStorage::class)->isR2Path($storedPath)) {
            return $storedPath;
        }

        // كل استجابة تكرر نفس الملف كثيرًا (شعار الجنسية داخل كل عاملة مثلًا)
        // و ManagedFileStorage::url() يعمل نداء شبكة لـ R2 في كل مرة.
        // الكاش فقط عند وجود نطاق عام (روابط دائمة)؛ الروابط الموقّعة تنتهي بعد 30 دقيقة
        // فلا يصح الاحتفاظ بها في worker طويل العمر.
        static $resolved = [];

        $cacheable = filled(\App\Support\R2StorageSettings::all()['public_url'] ?? null);

        if (!$cacheable) {
            return showFile($storedPath) ?: $fallback;
        }

        if (!array_key_exists($storedPath, $resolved)) {
            $resolved[$storedPath] = showFile($storedPath);
        }

        return $resolved[$storedPath] ?: $fallback;
    }
}

if (!function_exists('uploadMaidFile')) {
    function uploadMaidFile(\Illuminate\Http\UploadedFile $file, string $path)
    {
        return app(\App\Services\ManagedFileStorage::class)->uploadMaidFile($file, $path);
    }
}

if (!function_exists('showSignatureFile')) {
    function showSignatureFile($storedPath)
    {
        if (!is_string($storedPath) || $storedPath === '') {
            return null;
        }

        if (str_starts_with($storedPath, \App\Services\ManagedFileStorage::R2_PREFIX) || str_contains($storedPath, '/')) {
            return showFile($storedPath);
        }

        return showFile('signatures/' . $storedPath);
    }
}

if (!function_exists('documentSettingsRecord')) {
    function documentSettingsRecord()
    {
        static $resolved = false;
        static $settings = null;

        if ($resolved) {
            return $settings;
        }

        $resolved = true;

        if (!class_exists(Setting::class) || !\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return null;
        }

        $settings = Setting::with('translations')->first();

        return $settings;
    }
}

if (!function_exists('documentSetting')) {
    function documentSetting(string $key, $default = null, ?string $locale = null)
    {
        $companyKeys = ['document_company_name', 'phone', 'other_phone', 'email', 'location', 'tax_number', 'company_license_number'];
        if (in_array($key, $companyKeys, true) && ($company = \App\Models\CompanyProfile::current())) {
            $locale = $locale ?: app()->getLocale();
            $value = match ($key) {
                'document_company_name' => $locale === 'en' && $company->name_en ? $company->name_en : $company->name,
                'other_phone' => $company->phone2,
                'location' => $company->address,
                'company_license_number' => $company->license_number,
                default => $company->{$key},
            };
            return blank($value) ? $default : $value;
        }
        $settings = documentSettingsRecord();

        if (!$settings) {
            return $default;
        }

        $translatedAttributes = (array) ($settings->translatedAttributes ?? []);

        if (in_array($key, $translatedAttributes, true)) {
            $locale = $locale ?: app()->getLocale();
            $translations = collect($settings->translations ?? []);
            $translation = $translations->firstWhere('locale', $locale)
                ?: $translations->firstWhere('locale', config('app.fallback_locale'))
                ?: $translations->first();

            $value = $translation->{$key} ?? null;
        } else {
            $value = $settings->{$key} ?? null;
        }

        return blank($value) ? $default : $value;
    }
}

if (!function_exists('documentLogoUrl')) {
    function documentLogoUrl($default = null)
    {
        $logo = documentSetting('document_logo') ?: documentSetting('logo_header');
        $url = showFile($logo);

        return $url ?: $default;
    }
}

if (!function_exists('documentLogoPath')) {
    function documentLogoPath()
    {
        $logo = documentSetting('document_logo') ?: documentSetting('logo_header');

        if (!is_string($logo) || $logo === '') {
            return null;
        }

        $path = public_path('storage/' . ltrim($logo, '/'));

        return file_exists($path) ? $path : null;
    }
}

if (!function_exists('documentLogoDataUri')) {
    /**
     * شعار المستندات كـ data URI صالح لمولّدات PDF (mPDF/dompdf).
     * يجلب الشعار من التخزين المحلي أو R2، ويحوّل WebP إلى PNG لأن mPDF لا يدعمها،
     * ويحفظ النتيجة في الكاش لتجنّب تكرار الجلب والتحويل.
     */
    function documentLogoDataUri(): ?string
    {
        $logo = documentSetting('document_logo') ?: documentSetting('logo_header');

        if (!is_string($logo) || $logo === '') {
            return null;
        }

        return \Illuminate\Support\Facades\Cache::remember(
            'document_logo_data_uri:' . md5($logo),
            now()->addHours(6),
            function () use ($logo): ?string {
                try {
                    $localPath = documentLogoPath();
                    if ($localPath) {
                        $contents = file_get_contents($localPath);
                    } else {
                        $storage = app(\App\Services\ManagedFileStorage::class);
                        $contents = $storage->exists($logo) ? $storage->contents($logo) : null;
                    }

                    if (!$contents) {
                        return null;
                    }

                    $info = @getimagesizefromstring($contents);
                    $mime = $info['mime'] ?? '';

                    if (in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
                        return 'data:' . $mime . ';base64,' . base64_encode($contents);
                    }

                    // WebP وغيرها: التحويل إلى PNG مع الحفاظ على الشفافية.
                    $image = @imagecreatefromstring($contents);
                    if ($image === false) {
                        return null;
                    }

                    imagesavealpha($image, true);
                    ob_start();
                    imagepng($image);
                    $png = (string) ob_get_clean();
                    imagedestroy($image);

                    return $png !== '' ? 'data:image/png;base64,' . base64_encode($png) : null;
                } catch (\Throwable $e) {
                    report($e);

                    return null;
                }
            }
        );
    }
}

if (!function_exists('replaceDocumentPlaceholders')) {
    function replaceDocumentPlaceholders($text, array $replacements = [])
    {
        if ($text === null) {
            return null;
        }

        $branding = [
            'company_name' => documentSetting('document_company_name', documentSetting('website_name', config('app.name'))),
            'office_name' => documentSetting('document_office_name', documentSetting('website_name', config('app.name'))),
        ];

        $tokens = [
            '[اسم_الشركة]' => $branding['company_name'],
            '[اسم_المكتب]' => $branding['office_name'],
            '[company_name]' => $branding['company_name'],
            '[office_name]' => $branding['office_name'],
        ];

        foreach ($replacements as $key => $value) {
            $tokens['[' . $key . ']'] = (string) $value;
        }

        return strtr((string) $text, $tokens);
    }
}

if (!function_exists('documentBranding')) {
    function documentBranding(?string $locale = null): array
    {
        $locale = $locale ?: app()->getLocale();
        $defaultCompanyName = documentSetting('website_name', config('app.name'), $locale);
        $companyName = documentSetting('document_company_name', $defaultCompanyName, $locale);
        $officeName = documentSetting('document_office_name', $defaultCompanyName, $locale);
        $footerTemplate = documentSetting(
            'document_footer_note',
            'تم إنشاء هذا النموذج إلكترونياً بواسطة [اسم_الشركة].',
            $locale
        );

        return [
            'company_name' => $companyName,
            'office_name' => $officeName,
            'logo_url' => documentLogoUrl(asset('admin/assets/img/company_logo.jpeg')),
            'logo_path' => documentLogoPath(),
            'footer_note' => replaceDocumentPlaceholders($footerTemplate, [
                'اسم_الشركة' => $companyName,
                'اسم_المكتب' => $officeName,
                'company_name' => $companyName,
                'office_name' => $officeName,
            ]),
        ];
    }
}

if (!function_exists('imageOrNull')) {
    function imageOrNull($fileFullPath)
    {
        return showFile($fileFullPath) ?: '';
    }
}

function getFile($fileFullPath)
{
    return showFile($fileFullPath) ?: asset('/storage/uploads/noImage.gif');
}

if (!function_exists('getAuthUser')) {
    function getAuthUser()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user == null) {
            $user = \Illuminate\Support\Facades\Auth::guard('admin')->user();
        }
        return $user;
    }
}

if (!function_exists('getGuardedUser')) {
    function getGuardedUser($column = null)
    {
        $user = Auth::guard(config('auth.defaults.guard'))->user();

        if (!$user) {
            return null; // Or handle it in a way that fits your application, like throwing an exception or returning a default value.
        }

        if ($column) {
            return $user->{$column};
        }

        return $user;
    }
}

if (!function_exists('extractModelName')) {
    function extractModelName($migration)
    {
        preg_match('/create_(\w+)_table/i', pathinfo($migration, PATHINFO_FILENAME), $matches);

        $modelName = $matches[1] ?? null;

        return $modelName !== null ? Str::singular(Str::studly($modelName)) : null;
    }
}

if (!function_exists('getMigrations')) {
    function getMigrations($projectName)
    {
        $path = database_path("migrations/{$projectName}");

        $migrations = [];
        foreach (scandir($path) as $file) {
            $filePath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $migrations[] = $file;
            }
        }

        return $migrations;
    }
}

if (!function_exists('carbonParse')) {
    function carbonParse($date)
    {
        return Illuminate\Support\Carbon::parse($date);
    }
}




if (!function_exists('getImgTag')) {
    function getImgTag($fileFullPath, $width = "150px", $height = "120px")
    {
        $image_path = showFile($fileFullPath);
        // if (file_exists('/storage/app/public/' . $fileFullPath)) {
        //     $image = '<img src="' . $image_path . '" alt="profile-image" style="height: ' . $height . '; width: ' . $width . ';" class="avatar rounded me-2">';
        // } else {
        //     $image = '<img src="' . getFile($image_path) . '" alt="profile-image" style="height: ' . $height . '; width: ' . $width . ';" class="avatar rounded me-2">';
        // }
        $image = '<img src="' . e($image_path) . '" alt="profile-image" style="height: ' . $height . '; width: ' . $width . ';" class="avatar rounded me-2">';
        return $image;
    }
}

if (!function_exists('getPhoneWithTag')) {
    function getPhoneWithTag($phone)
    {
        if ($phone) {
            // الهاتف نص حر من نموذج التواصل العام: نُبقي في رابط tel: الأرقام و+ فقط،
            // ونهرّب النص المعروض.
            $telHref = preg_replace('/[^0-9+]/', '', (string) $phone);
            $phoneWithTag = '<a href="tel:' . e($telHref) . '">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone text-secondary" viewBox="0 0 16 16">
                            <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.68.68 0 0 0 .178.643l2.457 2.457a.68.68 0 0 0 .644.178l2.189-.547a1.75 1.75 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.6 18.6 0 0 1-7.01-4.42 18.6 18.6 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877z"/>
                </svg> ' . e($phone) . ' </a>';
        } else {
            $phoneWithTag = "لم يسجل هاتف";
        }
        return $phoneWithTag;
    }
}

if (!function_exists('getEmailWithTag')) {
    function getEmailWithTag($email)
    {
        if ($email) {
            $emailWithTag = '<a href="mailto:' . e($email) . '">' . e($email) . '</a>';
        } else {
            $emailWithTag = "لم يسجل ايميل";
        }
        return $emailWithTag;
    }
}

if (!function_exists('getRandomStringRandomInt')) {
    function getRandomStringRandomInt($length = 16)
    {
        $stringSpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pieces = "";
        $max = mb_strlen($stringSpace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces .= $stringSpace[random_int(0, $max)];
        }
        return $pieces;
    }
}

if (!function_exists('getFieldLanguage')) {
    function getFieldLanguage($locale)
    {
        return isset(Constant::globalArray['project_language'][$locale]) ? Constant::globalArray['project_language'][$locale] : '#';
    }
}

if (!function_exists('firstFilterAccount')) {
    function firstFilterAccount($dataSearch)
    {
        $show_type = $dataSearch["show_type"];
        $accountStatment = DB::table('constraint_details')
            ->select([
                'constraint_details.date',
                'constraint_details.statement',
                'constraint_details.debtor_value as first_debtor_value',
                'constraint_details.creditor_value as first_creditor_value',
                'constraint_details.type',
                'accounts.name as account_name',
                "accounts.status",
                'constraint_details.constraint_id',
            ])
            ->join('accounts', 'accounts.id', '=', 'constraint_details.account_id')
            ->when($show_type == 1, function ($q) use ($dataSearch) {
                return $q->whereDate("constraint_details.date", "<", $dataSearch["from_date"]);
            })
            ->orderBy("constraint_details.date", "ASC")->get();
        return $accountStatment;
    }
}

function api(): \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
{
    return auth()->guard('api');
}

if (!function_exists('helperTrans')) {
    function helperTrans($str): string
    {
        // Validate input
        if (empty($str) || !is_string($str)) {
            return '';
        }

        $arrayOfKeys = explode('.', $str);
        $file = $arrayOfKeys[0] ?? 'file';
        $key = $arrayOfKeys[1] ?? '';

        if (empty($file) || empty($key)) {
            return $str;
        }

        $local = get_api_lang();
        $lang_file_path = resource_path("lang/$local/$file.php");

        // Check if language file exists
        if (!file_exists($lang_file_path)) {
            return __($str);
        }

        try {
            $lang_array = include $lang_file_path;

            if (!is_array($lang_array)) {
                return __($str);
            }

            if (array_key_exists($key, $lang_array)) {
                return __($str);
            }
        } catch (\Exception $exception) {
            return __($str);
        }

        return ucfirst(str_replace('_', ' ', remove_invalid_characters($key)));
    }
}

function remove_invalid_characters($str): string
{
    return str_ireplace(['\'', '"', ',', ';', '<', '>'], ' ', $str);
}

function get_api_lang(): string
{
    $locale = request()->header('lang') ?: request()->get('lang') ?: app()->getLocale();
    $supportedLocales = array_keys(config('laravellocalization.supportedLocales', [])) ?: config('translatable.locales', ['ar', 'en']);

    return in_array($locale, $supportedLocales, true) ? $locale : config('app.locale', 'ar');
}

if (!function_exists('generalReturn')) {
    function generalReturn($request, $sql, $resource, $message = "success")
    {
        $jsonMsg = ["message" => $message, "code" => 200];
        $pagination_status = ($request->pagination) ? $request->pagination : "off";
        $limit_per_page = ($request->limit_per_page) ? $request->limit_per_page : "10";
        if ($pagination_status == 'on') {
            $data = $sql->paginate($limit_per_page);
            $json = array_merge($jsonMsg, ["data" => $resource::collection($data)]);
            $json = array_merge($json, collect($data)->except(["data", 'links'])->toArray());
            return response()->json($json, 200);
        }
        $json = array_merge($jsonMsg, ["data" => $resource::collection($sql->get())]);
        return response()->json($json, 200);
    }
}

// return only numbers from :
if (!function_exists('getWhatsAppNumber')) {
    function getWhatsAppNumber()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return null;
            }

            $whatsappNumber = Setting::query()->whereKey(1)->value('whatsapp');

            return $whatsappNumber ? preg_replace('/[^0-9]/', '', $whatsappNumber) : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }
}

if (!function_exists('viewSingleButton')) {
    function viewSingleButton($route)
    {
        return '<a class="btn btn-sm btn-info btn-view" href="' . $route . '" title="عرض التفاصيل">
                   <i class="fa fa-eye"></i>
                </a>';
    }
}

if (!function_exists('ahxEditBtn')) {
    function ahxEditBtn($route)
    {
        return '<a class="btn btn-sm btn-primary btn-edit" href="' . $route . '" title="تعديل">
                <i class="fa fa-pencil"></i>
            </a>';
    }
}

if (!function_exists('ahxCalcContractRemaining')) {
    function ahxCalcContractRemaining($contractId)
    {
        $remainingDays = 0;
        $contract = Contract::find($contractId);

        if (!$contract) {
            return $remainingDays; // Contract not found, return 0
        }

        $info = ContractInfo::where('contract_id', $contractId)->first();

        if (!$info || !$info->contract_date || !$info->contract_duration) {
            return $remainingDays; // Contract info not found or incomplete, return 0
        }

        try {
            $contractDate = new DateTime($info->contract_date);
            $contractDurationInDays = (int) $info->contract_duration;
            $todaysDate = new DateTime();

            // Calculate end date of contract
            $endDate = clone $contractDate;
            $endDate->modify('+' . $contractDurationInDays . ' days');

            // Calculate remaining days
            if ($todaysDate < $endDate) {
                $remainingDays = $todaysDate->diff($endDate)->days;
            }
        } catch (Exception $e) {
            return 0;
        }

        return $remainingDays;
    }
}

if (!function_exists('ahxMaidContractRemaining')) {
    function ahxMaidContractRemaining($id)
    {
        $maid = Maid::find($id);

        if (!$maid) {
            return 0; // Maid not found, return 0
        }

        $todaysDate = new DateTime();

        // Get all contracts with status 'sari' for the given maid
        $contracts = Contract::where('status', 'sari')
            ->where('maid_id', $id)
            ->get();

        foreach ($contracts as $contract) {
            $info = ContractInfo::where('contract_id', $contract->id)->first();

            if (!$info || !$info->contract_date || !$info->contract_duration) {
                continue; // Skip if contract info is invalid or incomplete
            }

            $contractDate = new DateTime($info->contract_date);
            // If the contract start date is today or in the future
            return ahxCalcContractRemaining($contract->id);
        }

        // No valid contract found
        return 0;
    }
}

function getContractInfo($contractId)
{
    $contract = Contract::find($contractId);
    if ($contract) {
        return ContractInfo::where('contract_id', $contractId)->first() ?? [];
    }
    return [];
}

function generateRandomCodeAhx($length)
{
    // Define the characters that can be used in the code
    $characters = '0123456789';
    $charactersLength = strlen($characters);
    $randomCode = '';

    // Loop to generate random characters
    for ($i = 0; $i < $length; $i++) {
        $randomCode .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomCode;
}

function getNationalityName($id)
{
    $data = Nationality::find($id);
    if (!$data) {
        return null;
    }
    return $data->title ?? 'not found';
}


if (!function_exists('ahxWorkerContractRemaining')) {
    function ahxWorkerContractRemaining($id)
    {
        $maid = ShelterWorkers::find($id);

        if (!$maid) {
            return 0; // Maid not found, return 0
        }

        $todaysDate = new DateTime();

        // Get all contracts with status 'sari' for the given maid
        $contracts = Contract::where('status', 'sari')
            ->where('maid_id', $id)
            ->get();

        foreach ($contracts as $contract) {
            $info = ContractInfo::where('contract_id', $contract->id)->first();

            if (!$info || !$info->contract_date || !$info->contract_duration) {
                continue; // Skip if contract info is invalid or incomplete
            }

            $contractDate = new DateTime($info->contract_date);
            // If the contract start date is today or in the future
            return ahxCalcContractRemaining($contract->id);
        }

        // No valid contract found
        return 0;
    }
}

if (!function_exists('getContractStatus')) {
    function getContractStatus($status)
    {
        $statuses = [
            'new' => [
                'ar' => 'عقود جديدة',
                'en' => 'New Contracts'
            ],
            'msaned' => [
                'ar' => 'عقود مساند',
                'en' => 'Msaned Contract'
            ],
            'tafwed' => [
                'ar' => 'تفويض',
                'en' => 'Tafwed'
            ],
            'taffez' => [
                'ar' => 'التفييز ',
                'en' => 'Taffez'
            ],
            'tazker' => [
                'ar' => 'تذكرة',
                'en' => 'Tazker'
            ],
            'under_warranty' => [
                'ar' => 'تحت الضمان',
                'en' => 'Under Warranty'
            ],
            'back' => [
                'ar' => 'مرتجع',
                'en' => 'Back'
            ],
            'accreditation_contracts' => [
                "ar" => "اعتماد العقود",
                "en" => "accreditation contracts"
            ],
            'arrival' => [
                'ar' => 'مرحلة الوصول',
                'en' => 'Arrival'
            ],
            'sari' => [
                'ar' => 'عقد ساري',
                'en' => 'Active Contract'
            ],
            'closed' => [
                'ar' => 'عقد مغلق',
                'en' => 'Closed Contract'
            ],
            'shelter' => [
                'ar' => 'عقد إيواء',
                'en' => 'Shelter Contract'
            ],
            'pending_maid_selection' => [
                'ar' => 'بانتظار اختيار العاملة',
                'en' => 'Pending Maid Selection'
            ],
            'completed' => [
                'ar' => 'عقد مكتمل',
                'en' => 'Completed Contract'
            ],
            'refunded' => [
                'ar' => 'مسترد',
                'en' => 'Refunded'
            ],
            'delegate_selected' => [
                'ar' => 'تم اختيار أحد المندوبين',
                'en' => 'Delegate Selected'
            ],
            'client_contracted' => [
                'ar' => 'تم التعاقد مع العميل',
                'en' => 'Client Contracted'
            ],
            'in_progress' => [
                'ar' => 'مرحلة الاجراءات',
                'en' => 'In Progress'
            ],
        ];

        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';

        return \App\Support\RecruitmentContractStageLabels::label((string) $status, $locale)
            ?? $statuses[$status][$locale]
            ?? $statuses[$status]['en']
            ?? $status;
    }
}

if (!function_exists('getContractStatusColor')) {
    function getContractStatusColor($status)
    {
        $colors = [
            'new' => 'warning',
            'msaned' => 'primary',
            'tafwed' => 'info',
            'taffez' => 'success',
            'tazker' => 'warning',
            'under_warranty' => 'primary',
            'back' => 'danger',
            'arrival' => 'success',
            'sari' => 'primary',
            'closed' => 'secondary',
            'shelter' => 'dark',
            'pending_maid_selection' => 'warning',
            'completed' => 'success',
            'refunded' => 'secondary',
            'delegate_selected' => 'info',
            'client_contracted' => 'primary',
            'in_progress' => 'warning',
            'accreditation_contracts' => 'dark'
        ];

        return $colors[$status] ?? 'secondary';
    }
}

if (!function_exists('getNextContractStatus')) {
    function getNextContractStatus($currentStatus)
    {
        $statusFlow = [
            'new' => 'msaned',
            'msaned' => 'tafwed',
            'tafwed' => 'taffez',
            'taffez' => 'tazker',
            'tazker' => 'arrival',
            'arrival' => 'sari',
            'sari' => 'closed',
            'pending_maid_selection' => 'new',
            'completed' => null,
            // لا توجد مرحلة تالية لهذه الحالات
            'under_warranty' => null,
            'back' => null,
            'closed' => null,
            'shelter' => null
        ];
        return $statusFlow[$currentStatus] ?? null;
    }
}

if (!function_exists('formatSaudiPhone')) {
    function formatSaudiPhone($phone)
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($phone, '00966')) {
            $phone = substr($phone, 2);
        }

        // Some user records contain a country code as well as a local/international
        // phone number. Collapse those combinations before sending to the gateway.
        while (str_starts_with($phone, '966966')) {
            $phone = substr($phone, 3);
        }

        if (str_starts_with($phone, '9660')) {
            $phone = '966' . substr($phone, 4);
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '05')) {
            $phone = '966' . substr($phone, 1);
        } elseif (strlen($phone) === 9 && str_starts_with($phone, '5')) {
            $phone = '966' . $phone;
        }

        return $phone;
    }
}


if (!function_exists('format_experience')) {
    function format_experience($experience_years)
    {
        $experience =
            is_numeric($experience_years) &&
            $experience_years > 0
            ? (float) $experience_years
            : 0;

        $years = floor($experience);
        $months = round(($experience - $years) * 12);

        $result = '';

        if ($years > 0) {
            $result .= $years . ' سنة';
        }

        if ($months > 0) {
            $result .= ($years > 0 ? ' و ' : '') . $months . ' أشهر';
        }

        return $result;
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney ($value) {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        $formatted = number_format((float) $value, 2);

        return rtrim(rtrim($formatted, '0'), '.');
    };
}


if (!function_exists('apiResponse')) {
    function apiResponse($status, $message, $data = null, $statusCode = 200){
        return response()->json([
            'status'  => $status,
            'message' => $message,
            'data'    => $data
        ], $statusCode, [], apiJsonEncodingOptions());
    }
}

if (!function_exists('helperTransGendered')) {
    /**
     * ترجمة تراعي جنس العامل/العاملة: تستخدم المفتاح "{key}_{gender}" إن وُجد في ملف اللغة
     * (مثل ingaz.experience_status_has_male)، وإلا تعود إلى المفتاح الأصلي بصيغته الحالية.
     */
    function helperTransGendered(string $key, string $gender): string
    {
        $genderedKey = $key . '_' . $gender;

        if (\Illuminate\Support\Facades\Lang::has($genderedKey, get_api_lang())) {
            return helperTrans($genderedKey);
        }

        return helperTrans($key);
    }
}
