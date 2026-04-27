<?php

namespace App\Http\Controllers\admin\office;

use App\Enums\Office\LanguagesTypesEnum;
use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\SkillAction;
use App\Http\Actions\office\OfficeFileAction;
use App\Http\Actions\office\OfficeFileSkillAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\office\OfficeFileRequest;
use App\Models\office\OfficeFile as objModel;
use Illuminate\Http\Request;

//use function App\Http\Controllers\ingaz\__;
//use function App\Http\Controllers\ingaz\response;
//use function App\Http\Controllers\ingaz\route;
//use function App\Http\Controllers\ingaz\view;

class OfficeFileController extends Controller
{

    public $folderPath = "admin.office.office_file.";
    public $postData = ["recruitment_office_id", "office_file_name", "job_title_id", "religion_id",
        "marital_status", "contract_duration", "passport_validity_period", "salary",
        "recruitment_price", "languages", "skillsIds", "passport_issuance_location", "education"];
    public $mainRoute = "offices-file";

    public function create(Request $request, JobTitleAction $jobTitleAction, ReligionAction $religionAction, SkillAction $skillAction, RecruitmentOfficeAction $recruitmentOfficeAction)
    {
        $office = $request->office_id
            ? $recruitmentOfficeAction->find($request->office_id)
            : null;

        if (!$office) {
            return redirect()->back();
        }
        $jobTitles = $jobTitleAction->get();
        $religions = $religionAction->get();
        $skills = $skillAction->get();
        $marital_statuses = MaritalStatusEnum::cases();
        $languages = LanguagesTypesEnum::cases();

        return view($this->folderPath . '.create', compact(
            'office',
            'jobTitles',
            'religions',
            'skills',
            'marital_statuses',
            'languages'
        ));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public
    function store(OfficeFileRequest $request, OfficeFileAction $action, OfficeFileSkillAction $officeFileSkillAction)
    {
        $data = $request->only($this->postData);
        $skillsIds = $data['skillsIds'] ?? [];
        unset($data['skillsIds']);

        $officeFile = $action->store($data);
        foreach ($skillsIds as $skillId) {
            $officeFileSkillAction->store([
                'office_file_id' => $officeFile->id,
                'skill_id' => $skillId,
            ]);
        }
        return jsonSuccess(['url' => route('offices.index')], null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function show($id)
    {

        $model = objModel::find($id);
        $model->is_show = !$model->is_show;
        $model->update();
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public
    function edit(Request $request, $id, OfficeFileAction $action, JobTitleAction $jobTitleAction, ReligionAction $religionAction, SkillAction $skillAction, RecruitmentOfficeAction $recruitmentOfficeAction)
    {
        $obj = $action->find($id);
        $office = $obj->office;
        $jobTitles = $jobTitleAction->get();
        $religions = $religionAction->get();
        $skills = $skillAction->get();
        $marital_statuses = MaritalStatusEnum::cases();
        $languages = LanguagesTypesEnum::cases();

        return view($this->folderPath . '.edit', compact(
            'office',
            'jobTitles',
            'religions',
            'skills',
            'marital_statuses',
            'languages',
            'obj'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(OfficeFileRequest $request, $id, OfficeFileAction $action, OfficeFileSkillAction $officeFileSkillAction)
    {
        $data = $request->only($this->postData);
        $obj = $action->find($id);
        $skillsIds = $data['skillsIds'] ?? [];
        unset($data['skillsIds']);
        $obj->update($data);
        $obj->skills()->sync($skillsIds);
        return jsonSuccess(['url' => route('offices.index')], null, 201);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id, OfficeFileAction $action, AdminAction $adminAction)
    {
        $obj = $action->find($id);
        if (isset($obj->officeManager->admin_id)) {
            $admin = $adminAction->find($obj->officeManager->admin_id);
            $adminAction->delete($admin->id);
        }
        $action->delete($id);
        return jsonSuccess();
    }
}
