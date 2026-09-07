<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\ingaz\EducationQualification;
use App\Models\ingaz\Maid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class EducationQualificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:' . \App\PermKeys::SETTINGS_CV_OPTIONS . ',admin');
    }

    public function index()
    {
        $qualifications = EducationQualification::orderBy('id')->get();

        $stats = [
            'total' => $qualifications->count(),
            'active' => $qualifications->where('is_active', true)->count(),
            'hidden' => $qualifications->where('is_active', false)->count(),
        ];

        $usageCounts = Schema::hasColumn('maids', 'education')
            ? Maid::whereIn('education', $qualifications->pluck('title_ar'))
                ->selectRaw('education, COUNT(*) as total')
                ->groupBy('education')
                ->pluck('total', 'education')
                ->all()
            : [];

        return view('admin.ingaz.education-qualifications.index', compact('qualifications', 'stats', 'usageCounts'));
    }

    public function create()
    {
        return view('admin.ingaz.education-qualifications.form', ['qualification' => new EducationQualification()]);
    }

    public function store(Request $request)
    {
        EducationQualification::create($this->validated($request));

        return redirect()->route('education-qualifications.index')->with('success', 'تمت إضافة المؤهل التعليمي بنجاح.');
    }

    public function edit(EducationQualification $educationQualification)
    {
        return view('admin.ingaz.education-qualifications.form', ['qualification' => $educationQualification]);
    }

    public function update(Request $request, EducationQualification $educationQualification)
    {
        $educationQualification->update($this->validated($request, $educationQualification));

        return redirect()->route('education-qualifications.index')->with('success', 'تم تعديل المؤهل التعليمي بنجاح.');
    }

    public function toggleVisibility(EducationQualification $educationQualification)
    {
        $educationQualification->update(['is_active' => !$educationQualification->is_active]);

        return redirect()->route('education-qualifications.index')->with('success',
            $educationQualification->is_active ? 'تم إظهار المؤهل التعليمي.' : 'تم إخفاء المؤهل التعليمي.');
    }

    public function destroy(EducationQualification $educationQualification)
    {
        $educationQualification->delete();

        return redirect()->route('education-qualifications.index')->with('success', 'تم حذف المؤهل التعليمي مع الاحتفاظ بقيمته في السير الموجودة.');
    }

    private function validated(Request $request, ?EducationQualification $qualification = null): array
    {
        return $request->validate([
            'title_ar' => ['required', 'string', 'max:255', Rule::unique('education_qualifications', 'title_ar')->ignore($qualification)],
            'title_en' => ['required', 'string', 'max:255', Rule::unique('education_qualifications', 'title_en')->ignore($qualification)],
        ], [], ['title_ar' => 'المؤهل بالعربية', 'title_en' => 'المؤهل بالإنجليزية']);
    }
}
