<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Support\ChartSettings;

/**
 * إعدادات الدليل المحاسبي لهذه الشركة: طريقة ترقيم الأكواد، وطريقة عرض «النوع».
 * الوضع الافتراضي لا يحفظ شيئاً، فلا يتغير شيء على من لا يغيّرها.
 */
class ChartSettingController extends Controller
{
    private const MAX_LEVELS = 6;
    private const MODE_DEFAULT = 'default';
    private const MODE_CUSTOM = 'custom';
    private const DISPLAY_SYSTEM = 'system';

    public function index()
    {
        $digits = ChartSettings::levelDigits();

        return view('finance::finance.chart_settings.index', [
            'bladeTitle' => 'إعدادات الدليل المحاسبي',
            'digits' => $digits,
            'maxLevels' => self::MAX_LEVELS,
            'current' => $this->lengthsByLevel(),
            'mismatched' => $this->mismatchedCount($digits),
            'mode' => $digits ? self::MODE_CUSTOM : self::MODE_DEFAULT,
            'typeDisplay' => ChartSettings::typeDisplayByHierarchy() ? ChartSettings::TYPE_DISPLAY_HIERARCHY : self::DISPLAY_SYSTEM,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numbering_mode' => ['required', 'in:' . self::MODE_DEFAULT . ',' . self::MODE_CUSTOM],
            'digits' => ['nullable', 'array'],
            'digits.*' => ['nullable', 'integer', 'min:1', 'max:18'],
            'type_display' => ['required', 'in:' . self::DISPLAY_SYSTEM . ',' . ChartSettings::TYPE_DISPLAY_HIERARCHY],
        ]);

        $digits = $data['numbering_mode'] === self::MODE_CUSTOM ? $this->normalizeDigits($data['digits'] ?? []) : [];

        ChartSettings::put(ChartSettings::LEVEL_DIGITS, $digits ? json_encode($digits) : null);
        ChartSettings::put(ChartSettings::TYPE_DISPLAY, $data['type_display'] === ChartSettings::TYPE_DISPLAY_HIERARCHY ? $data['type_display'] : null);

        $redirect = back()->with('success', 'حُفظت الإعدادات.');
        $mismatched = $this->mismatchedCount($digits);

        return $mismatched
            ? $redirect->with('warning', number_format($mismatched) . ' حساباً لا تطابق أكوادها الأطوال المحددة، ولن تتغير أكوادها. وفروعها الجديدة تأخذ الترقيم القديم حتى يُرتَّب الدليل.')
            : $redirect;
    }

    private function normalizeDigits(array $input): array
    {
        $digits = [];
        $previous = 0;
        for ($level = 1; $level <= self::MAX_LEVELS; $level++) {
            $value = $input[$level] ?? null;
            if ($value === null || $value === '') {
                break;
            }
            if ((int) $value <= $previous) {
                throw ValidationException::withMessages(['digits' => "طول المستوى {$level} يجب أن يكون أكبر من طول المستوى السابق."]);
            }
            $digits[$level] = $previous = (int) $value;
        }
        foreach ($input as $level => $value) {
            if ($value !== null && $value !== '' && !isset($digits[(int) $level])) {
                throw ValidationException::withMessages(['digits' => 'املأ المستويات بالتسلسل بلا فراغ بينها.']);
            }
        }
        if (!$digits) {
            throw ValidationException::withMessages(['digits' => 'أدخل عدد الخانات للمستوى الأول على الأقل، أو اختر الترقيم الافتراضي.']);
        }

        return $digits;
    }

    private function lengthsByLevel()
    {
        return DB::table('fn_accounts')->selectRaw('level, LENGTH(code) len, COUNT(*) n')
            ->groupBy('level', DB::raw('LENGTH(code)'))->orderBy('level')->orderBy('len')->get()->groupBy('level');
    }

    /** الحسابات التي لا يطابق طول كودها طول مستواها المحدد. */
    private function mismatchedCount(array $digits): int
    {
        $count = 0;
        foreach ($digits as $level => $length) {
            $count += DB::table('fn_accounts')->where('level', $level)->whereRaw('LENGTH(code) <> ?', [$length])->count();
        }

        return $count;
    }
}
