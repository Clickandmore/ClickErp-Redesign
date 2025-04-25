<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction as objAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\MaidRequest;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Maid as objModel;
use App\Models\ingaz\Nationality;
use App\Models\RecruitmentOffices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Yajra\DataTables\Facades\DataTables;

class MaidController extends Controller
{

    public $folderPath = "admin.ingaz.maids";
    public $postData = ["nationality_id", 'image', 'cv_file', 'marital_status:ar', 'marital_status:en', 'languages:ar', 'languages:en', 'experience_years', 'cv_preview', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'office_id'];
    public $mainRoute = "maids";

    public function __construct()
    {
        // $this->middleware('permission:العاملات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:العاملات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:العاملات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:العاملات-حذف,admin', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        $rows = objModel::latest()
        // ->take(40)
        // ->where('passport_num', 'EP8002663')
        ;
        if ($request->ajax()) {
            $this->applyMaidFilter($request, $rows);
            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    return $row->is_show == 1 ?
                    '<button class="btn btn-sm btn-success update-status" id="update-status" data-id="' . $row->id . '">ظاهر</button>' :
                    '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي </button>';
                })
                ->editColumn('available', function ($row) {
                    return $row->available == 1 ?
                    '<i class ="fa fa-check text-success">' : '<i class ="fa fa-times text-danger">';
                })
                ->editColumn('available_at', function ($row) {
                    return $row->available_at <= date('Y-m-d') ?
                    '<i class="fa fa-check text-success"></i>' :
                    $row->available_at;
                })
                ->editColumn('cv_file', function ($row) {
                    // Check if the `image` property is not null
                    if ($row->image) {
                        // Generate the image tag with the `image` property
                        $imgTag = '<img src="' . showFile($row->image) . '" alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">';
                    } else {
                        // Use `cv_preview` if available, otherwise fall back to `cv_file`
                        $imgUrl = $row->cv_preview ? showFile($row->cv_preview) : showFile($row->cv_file);
                        $imgTag = '<img src="' . $imgUrl . '" alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">';
                    }

                    // Wrap the image tag in a link
                    $link = '<a href="#" class="maidViewData" data-id="' . $row->id . '">' . $imgTag . '</a>';

                    return $link;
                })
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->editColumn('religion_id', function ($row) {
                    return ($row->religion) ? $row->religion->title : '';
                })
                ->editColumn('nationality_id', function ($row) {
                    return ($row->nationality) ? $row->nationality->title : '';
                })
                ->addColumn('maidContractRemaining', function ($row) {
                    $hasRemainingContracts = ahxMaidContractRemaining($row->id);
                    if ($hasRemainingContracts == 0) {
                        return __('ingaz.none');
                    } else {
                        return $hasRemainingContracts . ' ' . __('ingaz.days');
                    }
                })
                ->addColumn('actions', function ($row) {
                    $showButton = '';
                    $editButton = '';
                    $deleteButton = '';

                    $showButton = viewSingleButton(route('showMaidDetails', $row->id));

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL)) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)) {
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $showButton . " " . $editButton . " " . $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);

            return $dataTable;
        }

        // Fetch filter options for dropdowns
        $filter['nationalities'] = Nationality::latest()->get();
        $filter['offices'] = RecruitmentOffices::latest()->get();
        $filter['job_types'] = JobType::latest()->get();

        // Prepare data for the view
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans("ingaz.maids");
        $data["addButtonText"] = helperTrans("ingaz.maid");
        $data["modalType"] = "modal-lg";

        // Pass filter options to the view
        return view($this->folderPath . '.index', $data, compact('filter', 'request'));
    }

    public function noneAvailableMaids(Request $request, objAction $Action)
    {
        $rows = objModel::latest()
            ->where('available', '0')
        // ->take(15)
        // ->where('passport_num', 'EP8002663')
        ;
        if ($request->ajax()) {
            $this->applyMaidFilter($request, $rows);
            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    return $row->is_show == 1 ?
                    '<button class="btn btn-sm btn-success update-status" id="update-status" data-id="' . $row->id . '">ظاهر</button>' :
                    '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي </button>';
                })
                ->editColumn('available', function ($row) {
                    return $row->available == 1 ?
                    '<i class ="fa fa-check text-success">' : '<i class ="fa fa-times text-danger">';
                })
                ->editColumn('available_at', function ($row) {
                    return $row->available_at <= date('Y-m-d') ?
                    '<i class="fa fa-check text-success"></i>' :
                    $row->available_at;
                })
                ->editColumn('cv_file', function ($row) {
                    // Check if the `image` property is not null
                    if ($row->image) {
                        // Generate the image tag with the `image` property
                        $imgTag = '<img src="' . showFile($row->image) . '" alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">';
                    } else {
                        // Use `cv_preview` if available, otherwise fall back to `cv_file`
                        $imgUrl = $row->cv_preview ? showFile($row->cv_preview) : showFile($row->cv_file);
                        $imgTag = '<img src="' . $imgUrl . '" alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">';
                    }

                    // Wrap the image tag in a link
                    $link = '<a href="#" class="maidViewData" data-id="' . $row->id . '">' . $imgTag . '</a>';

                    return $link;
                })
                ->editColumn('created_at', function ($row) {
                    return ($row->created_at->format('Y-m-d h:i A'));
                })
                ->editColumn('religion_id', function ($row) {
                    return ($row->religion) ? $row->religion->title : '';
                })
                ->editColumn('nationality_id', function ($row) {
                    return ($row->nationality) ? $row->nationality->title : '';
                })
                ->addColumn('maidContractRemaining', function ($row) {
                    $hasRemainingContracts = ahxMaidContractRemaining($row->id);
                    if ($hasRemainingContracts == 0) {
                        return __('ingaz.none');
                    } else {
                        return $hasRemainingContracts . ' ' . __('ingaz.days');
                    }
                })
                ->addColumn('actions', function ($row) {
                    $showButton = '';
                    $editButton = '';
                    $deleteButton = '';

                    $showButton = viewSingleButton(route('showMaidDetails', $row->id));

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_CONTROL)) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $showButton . " " . $editButton . " " . $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);

            return $dataTable;
        }

        // Fetch filter options for dropdowns
        $filter['nationalities'] = Nationality::latest()->get();
        $filter['offices'] = RecruitmentOffices::latest()->get();
        $filter['job_types'] = JobType::latest()->get();

        // Prepare data for the view
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route('noneAvailableMaids');
        $data["bladeTitle"] = helperTrans("ingaz.maids");
        $data["addButtonText"] = helperTrans("ingaz.maid");
        $data["modalType"] = "modal-lg";

        // Pass filter options to the view
        return view($this->folderPath . '.none-ava', $data, compact('filter', 'request'));
    }

    public function showMaidDetails($id)
    {
        // dd($id);
        $maid = objModel::with('orders', 'contracts')->where('id', $id)->first();
        // dd($maid->orders);
        return view($this->folderPath . '.details', compact('maid'));
    }

    public function create(NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, Request $request)
    {
        if ($request->ajax()) {
            return $this->createAjaxResponse($act, $officeAct, $relAct, $jobTitleAction, $jobTypesAction, 'create');
        }
    }

    public function store(MaidRequest $request, objAction $action)
    {

        $data = $request->only($this->postData);
        $data = $action->storeMaid($data, $request);
        return jsonSuccess($data);
    }

    public function convert_page($id)
    {
        $data = objModel::where('id', $id)->first();
        return view($this->folderPath . '.convert_page', ['data' => $data]);
    }
    public function show($id)
    {
        $model = objModel::findOrFail($id);
        $model->is_show = !$model->is_show;
        $model->save(); // Save the changes
        return response()->json(['success' => true]);
    }

    public function edit(Request $request, $id, NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction)
    {
        if ($request->ajax()) {
            $obj = objModel::findOrFail($id);
            return $this->createAjaxResponse($act, $officeAct, $relAct, $jobTitleAction, $jobTypesAction, 'edit', ['obj' => $obj]);
        }
    }

    public function update(MaidRequest $request, objAction $action, $id)
    {
        $data = $request->only($this->postData);
        $data = $action->updateMaid($id, $data, $request);
        return jsonSuccess($data);
    }

    public function update_cv(Request $request, objAction $action, $id)
    {
        $data = $request->only($this->postData);

        $data = $action->updateMaid($id, $data, $request);
        return jsonSuccess($data);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }

    private function createAjaxResponse(NationalityAction $act, RecruitmentOfficeAction $officeAction, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, $viewName, $additionalData = [])
    {
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $offices = $officeAction->getAllOffices();
        $data = array_merge(compact('nationalities', 'offices', 'religions', 'jobs', 'types'), $additionalData);
        $returnHTML = view("{$this->folderPath}.{$viewName}", $data)->render();
        return jsonSuccess(['html' => $returnHTML]);
    }

    public function applyMaidFilter($request, $rows)
    {
        if ($request->has('passport_num') && $request->passport_num != null) {
            $rows->where('passport_num', 'like', '%' . $request->passport_num . '%');
        }
        if ($request->has('nationality_id') && $request->nationality_id != null) {
            $rows->where('nationality_id', $request->nationality_id);
        }
        if ($request->has('office_id') && $request->office_id != null) {
            $rows->where('office_id', $request->office_id);
        }
        if ($request->has('order_by') && $request->order_by != null) {
            if ($request->order_by == 'low_price') {
                $rows->orderBy('price', 'ASC');
            } elseif ($request->order_by == 'low_salary') {
                $rows->orderBy('salary', 'ASC');
            }

        }
        if ($request->has('job_type') && !empty($request->job_type)) {
            $rows->whereHas('jobTypes', function ($subQ) use ($request) {
                if (is_array($request->job_type)) {
                    $subQ->whereIn('job_type_id', $request->job_type);
                } else {
                    $subQ->where('job_type_id', $request->job_type);
                }
            });
        }
        if ($request->has('experience_years') && $request->experience_years != null) {
            $rows->where('experience_years', $request->experience_years);
        }
        if ($request->has('datefilter') && $request->datefilter != null) {
            $dates = explode(' - ', $request->datefilter);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $rows->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
    }

    public function getMaidDetails($maidId)
    {
        $maid = Maid::findOrFail($maidId);

        // Access the related nationality and religion
        $maid->nationality;
        $maid->religion;

        // Ensure the image URL is correctly formatted
        $maid->image = $maid->image ? showFile($maid->image) : null;
        $maid->cv_file = $maid->cv_file ? showFile($maid->cv_file) : null;

        // Determine the locale from the URL
        $locale = request()->segment(1); // Assuming language segment is the first part of the URL
        // dd($locale);
        // Set the translated religion title based on the detected locale
        if ($maid->religion->title == 'muslim') {
            $regTitle = $locale == 'ar' ? 'مسلم' : 'مسلم';
        } else {
            $regTitle = $locale == 'ar' ? 'غير مسلم' : 'غير مسلم';
        }

        $maid->religion_title = $regTitle;

        return response()->json($maid);
    }

}
