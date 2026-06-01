<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\PermKeys;
use App\Support\RecruitmentContractStageLabels;
use Illuminate\Http\Request;

class RecruitmentContractStageLabelController extends Controller
{
    public function edit()
    {
        abort_unless(AdminGuardHasPerm(PermKeys::SETTINGS_VIEW), 403);

        return view('admin.ingaz.recruitment_contract_stage_labels.edit', [
            'labels' => RecruitmentContractStageLabels::labels(),
            'defaults' => RecruitmentContractStageLabels::defaults(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(AdminGuardHasPerm(PermKeys::SETTINGS_CONTROL), 403);

        $validated = $request->validate([
            'labels' => ['required', 'array'],
            'labels.*.ar' => ['nullable', 'string', 'max:100'],
            'labels.*.en' => ['nullable', 'string', 'max:100'],
        ]);

        if ($request->boolean('reset_defaults')) {
            RecruitmentContractStageLabels::reset();
        } else {
            RecruitmentContractStageLabels::save($validated['labels']);
        }

        return redirect()
            ->route('recruitment-contract-stage-labels.edit')
            ->with('success', 'تم حفظ مسميات مراحل العقود بنجاح');
    }
}
