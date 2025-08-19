<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\MaidAction as objAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\ingaz\RecruitmentOfficeAction;
use App\Http\Actions\ingaz\ReligionAction;
use App\Http\Actions\ingaz\SkillAction;
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
    public $postData = ["nationality_id", 'image', 'cv_file', 'marital_status:ar', 'marital_status:en', 'languages', 'experience_years', 'cv_preview', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'office_id', 'name:en', 'type_id', 'job_id', 'skills', 'birth_date', 'height', 'weight', 'id_number', 'id_expiry_date', 'create_shelter_contract'];
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
        $query = objModel::query()
            ->with(['nationality', 'religion', 'jobTypes', 'contracts', 'orders']) // Eager load relationships
            ->select(['id', 'image', 'cv_file', 'cv_preview', 'nationality_id', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'is_show', 'available', 'available_at', 'created_at', 'is_pinned', 'status']) // Added status field
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->ajax()) {
            $this->applyMaidFilter($request, $query);

            return DataTables::of($query)
                ->addIndexColumn()
                ->orderColumn('id', function ($query, $order) {
                    $query->orderBy('id', $order);
                })
                ->orderColumn('nationality_id', function ($query, $order) {
                    $query->orderBy('nationality_id', $order);
                })
                ->orderColumn('job_type', function ($query, $order) {
                    $query->orderBy('id', $order);
                })
                ->orderColumn('passport_num', function ($query, $order) {
                    $query->orderBy('passport_num', $order);
                })
                ->orderColumn('price', function ($query, $order) {
                    $query->orderBy('price', $order);
                })
                ->orderColumn('salary', function ($query, $order) {
                    $query->orderBy('salary', $order);
                })
                ->orderColumn('religion_id', function ($query, $order) {
                    $query->orderBy('religion_id', $order);
                })
                ->orderColumn('age', function ($query, $order) {
                    $query->orderBy('age', $order);
                })
                ->orderColumn('created_at', function ($query, $order) {
                    $query->orderBy('created_at', $order);
                })
                ->editColumn('is_show', function ($row) {
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS)) {
                        return $row->is_show == 1 ?
                        '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '" title="انقر لتغيير الحالة إلى مخفي"><i class="fa fa-eye"></i> ظاهر</button>' :
                        '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '" title="انقر لتغيير الحالة إلى ظاهر"><i class="fa fa-eye-slash"></i> مخفي</button>';
                    } else {
                        return $row->is_show == 1 ? '<span class="badge bg-success"><i class="fa fa-eye"></i> ظاهر</span>' : '<span class="badge bg-dark"><i class="fa fa-eye-slash"></i> مخفي</span>';
                    }
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
                    if ($row->image) {
                        $imgTag = '<img src="' . showFile($row->image) . '" alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">';
                    } else {
                        $imgUrl = $row->cv_preview ? showFile($row->cv_preview) : showFile($row->cv_file);
                        $imgTag = '<img src="' . $imgUrl . '" alt="cv1" style="border-radius: 50%; object-fit: cover; width: 130px; height: 130px;">';
                    }
                    return '<a href="#" class="maidViewData" data-id="' . $row->id . '">' . $imgTag . '</a>';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d h:i A');
                })
                ->editColumn('religion_id', function ($row) {
                    return $row->religion ? $row->religion->title : '';
                })
                ->editColumn('nationality_id', function ($row) {
                    return $row->nationality ? $row->nationality->sub_title : '';
                })
                ->addColumn('maidContractRemaining', function ($row) {
                    $hasRemainingContracts = ahxMaidContractRemaining($row->id);
                    return $hasRemainingContracts == 0 ? __('ingaz.none') : $hasRemainingContracts . ' ' . __('ingaz.days');
                })
                ->addColumn('job_type', function ($row) {
                    return $row->jobTypes->pluck('title')->implode(', ');
                })
                ->addColumn('cv_status', function ($row) {
                    // تحديد حالة السيرة الذاتية بناءً على الحقول المتاحة
                    if ($row->status === 'backout') {
                        return '<span class="badge bg-danger">باك اوت</span>';
                    } elseif ($row->available == 0) {
                        return '<span class="badge bg-warning">محجوزة</span>';
                    } else {
                        // التحقق من وجود عقود أو طلبات نشطة
                        $hasActiveContracts = $row->contracts()->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'])->exists();
                        $hasActiveOrders = $row->orders()->whereIn('status', ['new', 'under_process', 'contracted'])->exists();

                        if ($hasActiveContracts || $hasActiveOrders) {
                            return '<span class="badge bg-warning">محجوزة</span>';
                        } else {
                            return '<span class="badge bg-success">متاحة</span>';
                        }
                    }
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '';

                    // Show CV details
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_SHOW)) {
                        $buttons .= viewSingleButton(route('showMaidDetails', $row->id));
                    }

                    // Edit CV
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_EDIT)) {
                        $buttons .= ahxEditBtn(route($this->mainRoute . ".edit", $row->id));
                    }

                    // Toggle pin status - only for available maids
                    $hasPinPermission = @AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_PIN);
                    if ($hasPinPermission && $row->available == 1) {
                        $buttons .= '<button class="btn btn-sm ' . ($row->is_pinned ? 'btn-warning' : 'btn-secondary') . ' toggle-pin" data-id="' . $row->id . '" data-pinned="' . ($row->is_pinned ? 'true' : 'false') . '" title="' . ($row->is_pinned ? 'إلغاء التثبيت' : 'تثبيت') . '" style="cursor: pointer;">
                             <i class="fa fa-thumb-tack"></i>
                        </button>';
                    }

                    // Delete CV
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_DELETE)) {
                        $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $buttons;
                })
                ->rawColumns(['is_show', 'available', 'available_at', 'cv_file', 'cv_status', 'actions'])
                ->make(true);
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

        return view($this->folderPath . '.index', $data, compact('filter', 'request'));
    }

    public function noneAvailableMaids(Request $request, objAction $Action)
    {
        $rows = objModel::latest()
            ->with(['nationality', 'religion', 'jobTypes', 'contracts', 'orders', 'unavailableRecords']) // Added unavailableRecords
            ->select(['id', 'image', 'cv_file', 'cv_preview', 'nationality_id', 'religion_id', 'passport_num', 'price', 'salary', 'age', 'is_show', 'available', 'available_at', 'created_at', 'is_pinned', 'status']) // Added status field
            ->where('status', 'backout')
            ->where('available', false)
        ;

        if ($request->ajax()) {
            // تطبيق الفلاتر الإضافية مع الحفاظ على فلتر backout
            $this->applyMaidFilterForBackout($request, $rows);

            $dataTable = DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('is_show', function ($row) {
                    if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS)) {
                        return $row->is_show == 1 ?
                        '<button class="btn btn-sm btn-success update-status" data-id="' . $row->id . '">ظاهر</button>' :
                        '<button class="btn btn-sm btn-dark update-status" data-id="' . $row->id . '">مخفي</button>';
                    } else {
                        return $row->is_show == 1 ? '<span class="badge bg-success">ظاهر</span>' : '<span class="badge bg-dark">مخفي</span>';
                    }
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
                ->addColumn('job_type', function ($row) {
                    return $row->jobTypes->pluck('title')->implode(', ');
                })
                ->addColumn('cv_status', function ($row) {
                    // تحديد حالة السيرة الذاتية بناءً على الحقول المتاحة
                    if ($row->status === 'backout') {
                        return '<span class="badge bg-danger">باك اوت</span>';
                    } elseif ($row->available == 0) {
                        return '<span class="badge bg-warning">محجوزة</span>';
                    } else {
                        // التحقق من وجود عقود أو طلبات نشطة
                        $hasActiveContracts = $row->contracts()->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'])->exists();
                        $hasActiveOrders = $row->orders()->whereIn('status', ['new', 'under_process', 'contracted'])->exists();

                        if ($hasActiveContracts || $hasActiveOrders) {
                            return '<span class="badge bg-warning">محجوزة</span>';
                        } else {
                            return '<span class="badge bg-success">متاحة</span>';
                        }
                    }
                })
                ->addColumn('unavailable_reason', function ($row) {
                    // جلب سبب عدم التوفر من جدول maid_unavailable_records
                    $unavailableRecord = $row->unavailableRecords()->latest()->first();
                    if ($unavailableRecord) {
                        return '<span class="reason-badge" title="' . htmlspecialchars($unavailableRecord->reason) . '">' .
                               htmlspecialchars(substr($unavailableRecord->reason, 0, 50)) .
                               (strlen($unavailableRecord->reason) > 50 ? '...' : '') . '</span>';
                    }
                    return '<span class="text-muted">لا يوجد سبب محدد</span>';
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
                ->rawColumns(['is_show', 'available', 'available_at', 'cv_file', 'cv_status', 'unavailable_reason', 'actions'])
                ->make(true);

            return $dataTable;
        }

        $bladeTitle = "العاملات باك اوت";

        // Fetch filter options for dropdowns
        $filter['nationalities'] = \App\Models\ingaz\Nationality::latest()->get();
        $filter['offices'] = \App\Models\RecruitmentOffices::latest()->get();
        $filter['job_types'] = \App\Models\ingaz\JobType::latest()->get();

        // Prepare data for the view
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route('noneAvailableMaids');
        $data["bladeTitle"] = $bladeTitle;
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

    public function create(NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, SkillAction $skillAction, Request $request)
    {
        if ($request->ajax()) {
            return $this->createAjaxResponse($act, $officeAct, $relAct, $jobTitleAction, $jobTypesAction, 'create');
        }

        // Handle direct request
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $offices = $officeAct->getAllOffices();
        $skills = $skillAction->get();

        return view("{$this->folderPath}.create", compact('nationalities', 'offices', 'religions', 'jobs', 'types', 'skills'));
    }

    public function store(MaidRequest $request, objAction $action)
    {
        $data = $request->all();
        // Set default status in Arabic
        $data['status'] = 'available';
        $row = $action->storeMaid($data, $request);
        return response()->json(['code' => 200, 'message' => 'تم إضافة العاملة بنجاح']);
    }

    // Helper method to get status in current language
    private function getStatusInCurrentLanguage($status)
    {
        $currentLocale = App::getLocale();
        foreach (Maid::STATUS_AVAILABLE as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_AVAILABLE[$currentLocale];
            }
        }
        foreach (Maid::STATUS_RESERVED as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_RESERVED[$currentLocale];
            }
        }
        foreach (Maid::STATUS_CONTRACTED as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_CONTRACTED[$currentLocale];
            }
        }
        foreach (Maid::STATUS_IN_ACCOMMODATION as $key => $value) {
            if ($value === $status) {
                return Maid::STATUS_IN_ACCOMMODATION[$currentLocale];
            }
        }
        return $status;
    }

    // Update status method
    public function updateStatus(Request $request, $id)
    {
        $maid = Maid::findOrFail($id);
        $newStatus = $request->input('status');

        // Validate status
        $validStatuses = [
            'available',
            'reserved',
            'contracted',
            'in_accommodation'
        ];

        if (!in_array($newStatus, $validStatuses)) {
            return response()->json([
                'code' => 400,
                'message' => 'حالة غير صالحة'
            ], 400);
        }

        $maid->status = $newStatus;
        $maid->save();

        return response()->json([
            'code' => 200,
            'message' => 'تم تحديث الحالة بنجاح',
            'status' => $this->getStatusInCurrentLanguage($newStatus)
        ]);
    }

    public function convert_page($id)
    {
        $data = objModel::where('id', $id)->first();
        return view($this->folderPath . '.convert_page', ['data' => $data]);
    }
        public function show($id)
    {
        // Check if user has permission to toggle status
        $hasPermission = @AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_STATUS);

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتغيير حالة الظهور'
            ], 403);
        }

        try {
            $model = objModel::findOrFail($id);
            $oldStatus = $model->is_show;
            $model->is_show = !$model->is_show;
            $model->save(); // Save the changes

            $status = $model->is_show ? 'ظاهر' : 'مخفي';
            $oldStatusText = $oldStatus ? 'ظاهر' : 'مخفي';

            // Log the action
            \Log::info("Maid visibility toggled", [
                'maid_id' => $id,
                'old_status' => $oldStatusText,
                'new_status' => $status,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "تم تغيير الحالة من {$oldStatusText} إلى {$status} بنجاح",
                'new_status' => $model->is_show,
                'old_status' => $oldStatus
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'السيرة الذاتية غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error toggling maid visibility', [
                'maid_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit(Request $request, $id, NationalityAction $act, RecruitmentOfficeAction $officeAct, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, SkillAction $skillAction)
    {
        if ($request->ajax()) {
            $obj = objModel::findOrFail($id);
            return $this->createAjaxResponse($act, $officeAct, $relAct, $jobTitleAction, $jobTypesAction, 'edit', ['obj' => $obj]);
        }

        // Handle direct request for edit page
        $obj = objModel::findOrFail($id);
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $offices = $officeAct->getAllOffices();
        $skills = $skillAction->get();

        return view("{$this->folderPath}.edit", compact('obj', 'nationalities', 'offices', 'religions', 'jobs', 'types', 'skills'));
    }

    public function update(MaidRequest $request, objAction $action, $id)
    {
        $data = $request->only($this->postData);
        $data = $action->updateMaid($id, $data, $request);

        if ($request->ajax()) {
            return jsonSuccess($data);
        }

        // Handle direct form submission
        return redirect()->route('maids.index')->with('success', 'تم تحديث العاملة بنجاح');
    }

    public function update_cv(Request $request, objAction $action, $id)
    {
        $data = $request->only($this->postData);

        $data = $action->updateMaid($id, $data, $request);
        return jsonSuccess($data);
    }

    public function togglePin($id)
    {
        \Log::info('Toggle pin method called for maid ID: ' . $id);

        // Check if user has permission to toggle pin
        $hasPermission = @AdminGuardHasPerm(\App\PermKeys::MAIDS_TOGGLE_PIN);
        \Log::info('User permission check for MAIDS_TOGGLE_PIN: ' . ($hasPermission ? 'true' : 'false'));

        if (!$hasPermission) {
            \Log::warning('User does not have permission to toggle pin');
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتثبيت/إلغاء تثبيت العاملة'
            ], 403);
        }

        try {
            $maid = objModel::findOrFail($id);
            \Log::info('Maid found: ' . $maid->name . ', current pin status: ' . ($maid->is_pinned ? 'true' : 'false'));

            $maid->is_pinned = !$maid->is_pinned;
            $maid->save();

            \Log::info('Maid pin status updated to: ' . ($maid->is_pinned ? 'true' : 'false'));

            $message = $maid->is_pinned ? 'تم تثبيت العاملة بنجاح' : 'تم إلغاء التثبيت بنجاح';

            return response()->json([
                'success' => true,
                'data' => $maid,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in togglePin method: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة التثبيت: ' . $e->getMessage()
            ], 500);
        }
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

    public function exportPdf()
    {
        $maids = objModel::with('nationality', 'religion', 'jobTypes', 'jobTitles')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة السيرة الذاتية</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .status-active {
                    color: green;
                }
                .status-blocked {
                    color: red;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة السيرة الذاتية</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم السيرة الذاتية</th>
                        <th>الاسم</th>
                        <th>رقم جواز السفر</th>
                        <th>الجنسية</th>
                        <th>الديانة</th>
                        <th>العمر</th>
                        <th>سنوات الخبرة</th>
                        <th>الراتب</th>
                        <th>التكلفة</th>
                        <th>أنواع الوظائف</th>
                        <th>الوظائف</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($maids as $maid) {
            $jobTypes = $maid->jobTypes->pluck('title')->implode(', ');
            $jobTitles = $maid->jobTitles->pluck('title')->implode(', ');
            $statusText = $maid->available ? 'متاح' : 'غير متاح';

            $html .= '<tr>
                <td>' . $maid->id . '</td>
                <td>' . $maid->name . '</td>
                <td>' . $maid->passport_num . '</td>
                <td>' . ($maid->nationality->title ?? '') . '</td>
                <td>' . ($maid->religion->title ?? '') . '</td>
                <td>' . $maid->age . '</td>
                <td>' . $maid->experience_years . '</td>
                <td>' . $maid->salary . '</td>
                <td>' . $maid->price . '</td>
                <td>' . $jobTypes . '</td>
                <td>' . $jobTitles . '</td>
                <td>' . $statusText . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="maids.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        $maids = objModel::with('nationality', 'religion', 'jobTypes', 'jobTitles')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم السيرة الذاتية',
            'الاسم',
            'رقم جواز السفر',
            'الجنسية',
            'الديانة',
            'العمر',
            'سنوات الخبرة',
            'الراتب',
            'التكلفة',
            'أنواع الوظائف',
            'الوظائف',
            'الحالة'
        ];

        // Set headers in first row
        $sheet->fromArray($headers, NULL, 'A1');

        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($maids as $maid) {
            $jobTypes = $maid->jobTypes->pluck('title')->implode(', ');
            $jobTitles = $maid->jobTitles->pluck('title')->implode(', ');
            $statusText = $maid->available ? 'متاح' : 'غير متاح';

            $sheet->setCellValue('A' . $row, $maid->id);
            $sheet->setCellValue('B' . $row, $maid->name);
            $sheet->setCellValue('C' . $row, $maid->passport_num);
            $sheet->setCellValue('D' . $row, $maid->nationality->title ?? '');
            $sheet->setCellValue('E' . $row, $maid->religion->title ?? '');
            $sheet->setCellValue('F' . $row, $maid->age);
            $sheet->setCellValue('G' . $row, $maid->experience_years);
            $sheet->setCellValue('H' . $row, $maid->salary);
            $sheet->setCellValue('I' . $row, $maid->price);
            $sheet->setCellValue('J' . $row, $jobTypes);
            $sheet->setCellValue('K' . $row, $jobTitles);
            $sheet->setCellValue('L' . $row, $statusText);
            $row++;
        }

        // Auto-size columns
        foreach(range('A','L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="maids.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'maids.xlsx', $headers)->deleteFileAfterSend(true);
    }

    private function createAjaxResponse(NationalityAction $act, RecruitmentOfficeAction $officeAction, ReligionAction $relAct, JobTitleAction $jobTitleAction, JobTypeAction $jobTypesAction, $viewName, $additionalData = [])
    {
        $nationalities = $act->getNationalities();
        $religions = $relAct->getReligions();
        $jobs = $jobTitleAction->getJobsTitles();
        $types = $jobTypesAction->getJobsTypes();
        $offices = $officeAction->getAllOffices();
        $skills = app(SkillAction::class)->get();
        $data = array_merge(compact('nationalities', 'offices', 'religions', 'jobs', 'types', 'skills'), $additionalData);
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

        // فلتر حالة السيرة الذاتية - تحديث ليدعم حالة backout
        if ($request->has('cv_status') && $request->cv_status != null) {
            if ($request->cv_status == 'available') {
                $rows->where('available', 1)
                    ->where('status', 'available')
                    ->whereDoesntHave('contracts', function($q) {
                        $q->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival']);
                    })
                    ->whereDoesntHave('orders', function($q) {
                        $q->whereIn('status', ['new', 'under_process', 'contracted']);
                    });
            } elseif ($request->cv_status == 'reserved') {
                $rows->where(function($q) {
                    $q->where('available', 0)
                      ->where('status', 'reserved')
                      ->orWhereHas('contracts', function($subQ) {
                          $subQ->whereIn('status', ['new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival']);
                      })
                      ->orWhereHas('orders', function($subQ) {
                          $subQ->whereIn('status', ['new', 'under_process', 'contracted']);
                      });
                });
            } elseif ($request->cv_status == 'backout') {
                // فلتر خاص للعاملات باك اوت
                $rows->where('status', 'backout')
                      ->where('available', 0);
            }
        }
    }

    public function applyMaidFilterForBackout($request, $rows)
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
        $maid = Maid::with(['nationality', 'religion', 'job_types.job_type'])
            ->findOrFail($maidId);

        // Ensure the image URL is correctly formatted
        $imageUrl = $maid->image ? showFile($maid->image) : null;
        $cvFileUrl = $maid->cv_file ? showFile($maid->cv_file) : null;
        $cvPreviewUrl = $maid->cv_preview ? showFile($maid->cv_preview) : $cvFileUrl;

        // Determine the locale from the URL
        $locale = request()->segment(1); // Assuming language segment is the first part of the URL

        // Set the translated religion title based on the detected locale
        if ($maid->religion && $maid->religion->title == 'muslim') {
            $regTitle = $locale == 'ar' ? 'مسلم' : 'Muslim';
        } else {
            $regTitle = $locale == 'ar' ? 'غير مسلم' : 'Non-Muslim';
        }

        // Get nationality title based on locale
        $nationalityTitle = '';
        if ($maid->nationality) {
            $nationalityTitle = $locale == 'ar' ? $maid->nationality->title : $maid->nationality->sub_title;
        }

        // Get job type title
        $jobTypeTitle = '';
        if ($maid->job_types && $maid->job_types->isNotEmpty()) {
            $jobType = $maid->job_types->first()->job_type;
            if ($jobType) {
                $jobTypeTitle = $jobType->title;
            }
        }

        // Ensure all required fields are present
        $response = [
            'id' => $maid->id,
            'name' => $maid->name,
            'passport_num' => $maid->passport_num,
            'religion_title' => $regTitle,
            'religion' => $regTitle,
            'nationality' => [
                'id' => $maid->nationality ? $maid->nationality->id : null,
                'title' => $nationalityTitle
            ],
            'job_types' => [
                [
                    'id' => $maid->job_types->first()->job_type->id ?? null,
                    'title' => $jobTypeTitle
                ]
            ],
            'job_title' => $jobTypeTitle,
            'occupation' => $jobTypeTitle,
            'salary' => $maid->salary,
            'price' => $maid->price,
            'experience_years' => $maid->experience_years,
            'age' => $maid->age,
            'birth_date' => $maid->birth_date,
            'height' => $maid->height,
            'weight' => $maid->weight,
            'marital_status' => $maid->marital_status ?? 'single',
            'image' => $imageUrl,
            'cv_file' => $cvFileUrl,
            'cv_preview' => $cvPreviewUrl
        ];

        return response()->json($response);
    }

    public function getOfficesByNationality(Request $request)
    {
        $offices = RecruitmentOffices::where('nationality_id', $request->nationality_id)
            ->with('translations')
            ->get()
            ->map(function ($office) {
                return [
                    'id' => $office->id,
                    'title' => $office->title
                ];
            });

        return response()->json($offices);
    }

    /**
     * Check if passport number is unique
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPassportUnique(Request $request)
    {
        $passportNum = $request->passport_num;

        if (empty($passportNum)) {
            return response()->json(['exists' => false]);
        }

        $exists = objModel::where('passport_num', $passportNum)->exists();

        return response()->json(['exists' => $exists]);
    }

    public function getAvailableMaids()
    {
        try {
            $maids = Maid::where('available', true)
                ->where('status', 'available')
                ->with(['nationality'])
                ->select(['id', 'name', 'passport_num', 'nationality_id'])
                ->get()
                ->map(function($maid) {
                    return [
                        'id' => $maid->id,
                        'name' => $maid->name,
                        'passport_num' => $maid->passport_num,
                        'nationality' => $maid->nationality ? $maid->nationality->title : ''
                    ];
                });

            return jsonSuccess(['maids' => $maids]);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء تحميل العاملات المتاحات']);
        }
    }

    public function searchMaids(Request $request)
    {
        try {
            $search = $request->get('search');

            $maids = Maid::where('available', true)
                ->where('status', 'available')
                ->where(function($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('passport_num', 'like', '%' . $search . '%');
                })
                ->with(['nationality'])
                ->select(['id', 'name', 'passport_num', 'nationality_id'])
                ->get()
                ->map(function($maid) {
                    return [
                        'id' => $maid->id,
                        'name' => $maid->name,
                        'passport_num' => $maid->passport_num,
                        'nationality' => $maid->nationality ? $maid->nationality->title : ''
                    ];
                });

            return jsonSuccess(['maids' => $maids]);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء البحث عن العاملات']);
        }
    }

    public function getMaidInfo($id)
    {
        try {
            $maid = Maid::with(['nationality', 'religion'])
                ->select(['id', 'name', 'passport_num', 'nationality_id', 'religion_id', 'age', 'experience_years'])
                ->findOrFail($id);

            return jsonSuccess([
                'maid' => [
                    'id' => $maid->id,
                    'name' => $maid->name,
                    'passport_num' => $maid->passport_num,
                    'nationality' => $maid->nationality ? $maid->nationality->title : '',
                    'age' => $maid->age,
                    'experience' => $maid->experience_years . ' سنوات'
                ]
            ]);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء تحميل معلومات العاملة']);
        }
    }

}
