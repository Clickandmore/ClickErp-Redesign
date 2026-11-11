<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * يقدّم أداة ترحيل البيانات القديمة (legacy/) من خلف تسجيل الدخول والصلاحيات.
 * كانت الأداة داخل public/ فيستطيع أي زائر تشغيلها وتنزيل ملفات العملاء المصدَّرة.
 */
class LegacyMigrationToolController extends Controller
{
    private const STATIC_TYPES = [
        'html' => 'text/html; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
    ];

    private const DOWNLOAD_TYPES = [
        'csv' => 'text/csv; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
    ];

    // مجلدات الملفات الناتجة عن الأداة؛ تُنزَّل فقط ولا يُنفَّذ منها شيء
    private const DOWNLOAD_DIRS = ['migration/exports', 'migration/reports'];

    public function root(Request $request)
    {
        return $this->serve($request, 'migration.php');
    }

    public function hub(Request $request)
    {
        return $this->serve($request, 'migration_hub.php');
    }

    public function script(Request $request, string $path)
    {
        return $this->serve($request, 'migration/' . $path);
    }

    private function serve(Request $request, string $relative)
    {
        $this->rejectCrossSiteWrites($request);

        $base = realpath(base_path('legacy'));
        $file = $base ? realpath($base . DIRECTORY_SEPARATOR . $relative) : false;

        if (!$file || !is_file($file) || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        $directory = ltrim(substr(dirname($file), strlen($base)), DIRECTORY_SEPARATOR);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // الكود يُنفَّذ أو يُعرض من مجلد الأداة نفسه فقط، لا من مجلدات الرفع أو التصدير
        if (in_array($directory, ['', 'migration'], true)) {
            if ($extension === 'php') {
                return $this->runScript($file);
            }

            if (isset(self::STATIC_TYPES[$extension])) {
                return response()->file($file, ['Content-Type' => self::STATIC_TYPES[$extension]]);
            }
        }

        if (in_array($directory, self::DOWNLOAD_DIRS, true) && isset(self::DOWNLOAD_TYPES[$extension])) {
            return response()->download($file, basename($file), ['Content-Type' => self::DOWNLOAD_TYPES[$extension]]);
        }

        abort(404);
    }

    private function runScript(string $file)
    {
        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__legacyScript) {
                require $__legacyScript;
            })($file);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        $output = '';
        while (ob_get_level() > $level) {
            $output = ob_get_clean() . $output;
        }

        return response($output, http_response_code() ?: 200)
            ->header('Content-Type', $this->scriptContentType());
    }

    private function scriptContentType(): string
    {
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                return trim(substr($header, strlen('Content-Type:')));
            }
        }

        return 'text/html; charset=UTF-8';
    }

    /**
     * سكربتات الأداة ترسل POST بدون رمز CSRF، لذلك هي مستثناة منه،
     * ونمنع بدلاً منه أي طلب كتابة قادم من موقع آخر.
     */
    private function rejectCrossSiteWrites(Request $request): void
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $source = $request->headers->get('Origin') ?: $request->headers->get('Referer');
        $sourceHost = $source ? parse_url($source, PHP_URL_HOST) : null;

        if (!$sourceHost || strcasecmp($sourceHost, $request->getHost()) !== 0) {
            abort(403);
        }
    }
}
