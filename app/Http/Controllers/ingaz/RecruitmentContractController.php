<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractStatusLog;
use App\Models\ingaz\Contact;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Country;
use App\Actions\OrderAction;
use App\Actions\MaidAction;
use App\Actions\ClientAction;
use App\Actions\NationalityAction;
use App\Actions\ReligionAction;
use App\Actions\JobTitleAction;
use App\Actions\JobTypeAction;
use Illuminate\Support\Facades\DB;
use App\Helpers\NewPermKeys;

class RecruitmentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RecruitmentContract";
    public $mainRoute = "RecruitmentContract";
    public $contractInfoData = [
        'visa_number',
        'visa_hijry_date',
        'visa_milad_date',
        'working_hours_num',
        'arrival_date',
        'sponsorship_price',
        'salary_after_sponsorship',
        'monthly_cost',
        'contract_duration' ,
        'contract_value',
        'contract_date',
        'ingaz_from',
        'ingaz_to',
        'previous_sponsorship',
        'msaned_number',
        'tafwed_number'
    ];


    public function index(Request $request)
    {
        // التحقق من صلاحية عرض العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض العقود'
            ], 403);
        }

        if ($request->ajax()) {


            $query = Contract::with([
                    'user', // جميع بيانات العميل
                    'user.nationality', // جنسية العميل (Country model)
                    'maid', // جميع بيانات العاملة
                    'maid.nationality.translations', // جنسية العاملة مع الترجمات
                    'maid.religion.translations', // ديانة العاملة مع الترجمات
                    'maid.office.translations', // المكتب الخارجي مع الترجمات
                    'requiredMaidNationality.translations', // الجنسية المفضلة للعاملة
                    'info', // جميع بيانات العقد
                    'info.shelter.translations', // بيانات الإيواء
                    'contract_status_logs', // سجل حالات العقد (مطلوب لبعض العمليات)
                    'order', // بيانات الطلب
                    'order.details', // تفاصيل الطلب
                    'order.details.admin' // بيانات المسوق
                ])
                ->whereIn('type', ['recruitment', 'maid']);

            // فلتر الحالة - استخدام query parameter فقط
            $statusFilter = $request->get('status');
            if ($statusFilter) {
                $query->where('status', $statusFilter);
                \Log::info('Status filter applied:', [
                    'request_status' => $request->get('status'),
                    'final_status_filter' => $statusFilter
                ]);
            }


            // فلتر التاريخ من
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // فلتر رقم جوال العميل
            if ($request->filled('client_phone')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->client_phone . '%');
                });
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // فلتر رقم جواز العاملة
            if ($request->filled('maid_passport')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->maid_passport . '%');
                });
            }

            // فلتر اسم العاملة
            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            // فلتر رقم العقد
            if ($request->filled('contract_number')) {
                $query->where('id', 'like', '%' . $request->contract_number . '%');
            }

            // فلتر جنسية العاملة
            if ($request->filled('nationality')) {
                $query->whereHas('maid.nationality.translations', function($q) use ($request) {
                    $q->where('sub_title', $request->nationality);
                });
            }

            // فلتر ديانة العاملة
            if ($request->filled('religion')) {
                $query->whereHas('maid.religion.translations', function($q) use ($request) {
                    $q->where('title', $request->religion);
                });
            }

            // فلتر المكتب الخارجي
            if ($request->filled('office_id')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('office_id', $request->office_id);
                });
            }

            // فلتر مدينة العميل
            if ($request->filled('city')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->city . '%');
                });
            }

            // فلتر المسوق
            if ($request->filled('marketer_id')) {
                $query->where(function($q) use ($request) {
                    // البحث في marketer_id المباشر
                    $q->where('marketer_id', $request->marketer_id)
                      // أو البحث في order.details.admin_id
                      ->orWhereHas('order.details', function($orderQuery) use ($request) {
                          $orderQuery->where('admin_id', $request->marketer_id);
                      });
                });
            }

            $rows = $query->orderBy('id', 'desc');

            \Log::info('Query prepared for DataTables', [
                'filters_applied' => $request->except(['draw', 'start', 'length', 'search', 'order', 'columns']),
                'status_filter' => $statusFilter
            ]);

            // استخدام DataTables::eloquent بدلاً من get() لتحسين الأداء
            return DataTables::eloquent($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    $statusText = getContractStatus($row->status);
                    $statusColor = getContractStatusColor($row->status);
                    $statusDisplay = '<span class="badge bg-' . $statusColor . '">' . $statusText . '</span>';

                    // إضافة تاريخ آخر تحديث
                    $lastUpdate = $row->updated_at ? $row->updated_at->format('Y-m-d') : '';
                    if ($lastUpdate) {
                        $statusDisplay .= '<br><small class="text-muted">آخر تحديث: ' . $lastUpdate . '</small>';
                    }

                    // إضافة حالة الضمان للعقود المكتملة
                    if ($row->status === 'completed') {
                        $warrantyStatus = $row->warranty_status ?? 'not_applicable';

                        // حساب الأيام المتبقية من تاريخ انتهاء الضمان
                        $remainingDays = 0;
                        if ($row->warranty_end_date) {
                            $endDate = \Carbon\Carbon::parse($row->warranty_end_date);
                            $remainingDays = max(0, $endDate->diffInDays(now(), false));
                            if ($remainingDays < 0) $remainingDays = abs($remainingDays);
                        }

                        switch ($warrantyStatus) {
                            case 'active':
                                $warrantyColor = 'success';
                                $warrantyIcon = 'fas fa-shield-alt';
                                $warrantyText = "ضمان نشط ({$remainingDays} يوم)";
                                break;
                            case 'expired':
                                $warrantyColor = 'danger';
                                $warrantyIcon = 'fas fa-times-circle';
                                $warrantyText = 'ضمان منتهي';
                                break;
                            case 'not_applicable':
                            default:
                                $warrantyColor = 'secondary';
                                $warrantyIcon = 'fas fa-minus-circle';
                                $warrantyText = 'لا يوجد ضمان';
                                break;
                        }

                        $statusDisplay .= '<br><small><span class="badge bg-' . $warrantyColor . '"><i class="' . $warrantyIcon . ' me-1"></i>' . $warrantyText . '</span></small>';
                    }

                    return $statusDisplay;
                })

                ->editColumn('phone', function ($row) {
                    $phoneInfo = '';
                    if ($row->user) {
                        $phoneInfo .= '<div class="user-info">';
                        $phoneInfo .= '<strong>' . $row->user->name . '</strong><br>';
                        $phoneInfo .= getPhoneWithTag($row->user->phone_code . $row->user->phone). '<br>';
                        if ($row->user->birthdate) {
                            $phoneInfo .= '<small class="text-muted mt-2">تاريخ الميلاد: ' . \Carbon\Carbon::parse($row->user->birthdate)->format('Y-m-d') . '</small>';
                        }
                        if ($row->user->national_num) {
                            $phoneInfo .= '<small class="text-muted mt-2">رقم الهوية: ' . $row->user->national_num . '</small>';
                        }
                        if ($row->user->address) {
                            $phoneInfo .= '<small class="text-muted mt-2">العنوان: ' . $row->user->address . '</small>';
                        }
                        $phoneInfo .= '</div>';
                    }
                    return $phoneInfo;
                })
                ->editColumn('maid_id', function ($row) {
                    $maidInfo = '';
                    if ($row->maid) {
                        $maidInfo .= '<div class="maid-info">';
                        // إضافة تعليق لصورة العاملة
                        // $maidInfo .= '<div class="maid-image-container" title="صورة العاملة: ' . $row->maid->name . '">';
                        // $maidInfo .= getImgTag($row->maid->cv_file, "80px", "80px");
                        // $maidInfo .= '</div>';
                        $maidInfo .= '<br><strong>' . $row->maid->name . '</strong>';
                        if ($row->maid->passport_num) {
                            $maidInfo .= '<br><small class="text-muted">جواز: ' . $row->maid->passport_num . '</small>';
                        }
                        if ($row->maid->nationality) {
                            $maidInfo .= '<small class="text-muted">الجنسية: ' . $row->maid->nationality->sub_title . '</small>';
                        }
                        if ($row->maid->office) {
                            $maidInfo .= '<small class="text-muted">المكتب الخارجي: ' . $row->maid->office->title . '</small>';
                        }
                        if ($row->maid->religion) {
                            $maidInfo .= '<small class="text-muted">الديانة: ' . $row->maid->religion->title . '</small>';
                        }
                       $maidInfo .= '</div>';
                    } else {
                        // رسالة محسنة عندما لا توجد عاملة مع إظهار الجنسية المفضلة
                        if ($row->status == ContractStatusEnum::PendingMaidSelection->value) {
                            $maidInfo = '<div class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i><strong>في انتظار اختيار العاملة</strong></div>';
                        } else {
                            $maidInfo = '<div class="text-danger"><i class="fas fa-times-circle me-1"></i><strong>لا توجد عاملة مخصصة</strong></div>';
                        }

                        // إضافة الجنسية المفضلة إذا كانت متوفرة
                        if ($row->requiredMaidNationality) {
                            $maidInfo .= '<div class="text-info mt-1"><i class="fas fa-flag me-1"></i><small>الجنسية المفضلة: <strong>' . $row->requiredMaidNationality->sub_title . '</strong></small></div>';
                        }
                    }
                    return $maidInfo;
                })
                ->addColumn('contract_info', function ($row) {
                    $contractInfo = '';
                    if ($row->info) {
                        $contractInfo .= '<div class="contract-info">';
                        if ($row->info->msaned_number) {
                            $contractInfo .= '<br><div><strong>مساند:</strong> ' . $row->info->msaned_number . '</div>';
                        }
                        // if ($row->info->tafwed_number) {
                        //     $contractInfo .= '<div><strong>تفويض:</strong> ' . $row->info->tafwed_number . '</div>';
                        // }
                        if ($row->info->visa_number) {
                            $contractInfo .= '<br><div><strong>تأشيرة:</strong> ' . $row->info->visa_number . '</div>';
                        }
                        if ($row->info->contract_date) {
                            $contractInfo .= '<br><div><strong>تاريخ العقد:</strong> ' . \Carbon\Carbon::parse($row->info->contract_date)->format('Y-m-d') . '</div>';
                        }
                        $contractInfo .= '</div>';
                    }
                    return $contractInfo ?: '<span class="text-muted">غير محدد</span>';
                })
                ->addColumn('marketer', function ($row) {
                    $marketerInfo = '';

                    // البحث عن المسوق من marketer_id المباشر
                    if ($row->marketer_id) {
                        $marketer = \App\Models\auth\Admin::find($row->marketer_id);
                        if ($marketer) {
                            $marketerInfo = '<div class="marketer-info">';
                            $marketerInfo .= '<i class="fas fa-user-tie me-1 text-primary"></i>';
                            $marketerInfo .= '<strong>' . htmlspecialchars($marketer->name) . '</strong>';
                            $marketerInfo .= '</div>';
                        }
                    }

                    // إذا لم يوجد marketer_id، البحث في order.details.admin
                    if (empty($marketerInfo) && $row->order && $row->order->details && $row->order->details->admin) {
                        $marketer = $row->order->details->admin;
                        $marketerInfo = '<div class="marketer-info">';
                        $marketerInfo .= '<i class="fas fa-user-tie me-1 text-info"></i>';
                        $marketerInfo .= '<strong>' . htmlspecialchars($marketer->name) . '</strong>';
                        $marketerInfo .= '<br><small class="text-muted">من الطلب</small>';
                        $marketerInfo .= '</div>';
                    }

                    return $marketerInfo ?: '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>غير محدد</span>';
                })

                ->addColumn('updateStatus', function ($row) {
                    if ($row->status == ContractStatusEnum::PendingMaidSelection->value) {
                        // زر إضافة عاملة للعقود التي في حالة بانتظار اختيار العاملة
                        return '<a href="' . route('RecruitmentContract.selectMaid', $row->id) . '" class="btn btn-warning btn-sm btn-action" title="إضافة عاملة">
                            <i class="fas fa-user-plus me-1"></i>إضافة عاملة
                        </a>';
                    } else if ($row->status == ContractStatusEnum::Tazker->value) {
                        // التحقق من صلاحيات إدارة الرحلات
                        $hasTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL);
                        $hasAddTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL);
                        $hasCompleteTravelPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL);

                        // check if there is a travel history for this contract
                        $travelHistory = TravelHistory::where('order_id', $row->id)->first();

                        if ($travelHistory) {
                            if ($travelHistory->status === 'completed') {
                                return '<span class="badge bg-success">تم إكمال الرحلة</span>';
                            } else {
                                // عرض زر إكمال الرحلة لجميع الرحلات غير المكتملة
                                if ($hasCompleteTravelPermission) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                } else {
                                    $statusText = $travelHistory->status === 'active' ? 'رحلة نشطة' : 'رحلة قيد الإعداد';
                                    return '<span class="badge bg-warning">' . $statusText . '</span>';
                                }
                            }
                        } else {
                            if ($hasAddTravelPermission) {
                                return '<button class="btn btn-primary btn-sm add-travel-btn btn-action" style="padding: 10px; margin: 10px;" data-contract-id="' . $row->id . '" title="إضافة رحلة">
                                    <i class="fas fa-plane me-1"></i>إضافة رحلة
                                </button>';
                            } else {
                                return '<span class="text-muted">لا توجد صلاحية لإضافة رحلة</span>';
                            }
                        }
                    } else {
                        // التحقق من الصلاحيات حسب حالة العقد
                        $hasPermission = false;
                        $permissionRequired = '';
                        $nextStatus = '';
                        $buttonText = '';

                        switch ($row->status) {
                            case ContractStatusEnum::New->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                                $nextStatus = ContractStatusEnum::Msaned->value;
                                $buttonText = 'انتقال إلى مساند';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Msaned->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                                $nextStatus = ContractStatusEnum::Tafwed->value;
                                $buttonText = 'انتقال إلى تفويض';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Tafwed->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                                $nextStatus = ContractStatusEnum::Taffez->value;
                                $buttonText = 'انتقال إلى تفييز';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Taffez->value:
                                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                                $nextStatus = ContractStatusEnum::Tazker->value;
                                $buttonText = 'انتقال إلى تذكرة';
                                if (@AdminGuardHasPerm($permissionRequired)) {
                                    $hasPermission = true;
                                }
                                break;
                            case ContractStatusEnum::Arrival->value:
                                // التحقق من صلاحية إغلاق العقد بعد الضمان
                                $nextStatus = ContractStatusEnum::Closed->value;
                                $buttonText = 'إغلاق العقد';
                                if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY)) {
                                    $hasPermission = true;
                                }

                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Sari->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Shelter->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::UnderWarranty->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Closed->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                        $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            case ContractStatusEnum::Completed->value:
                                // إضافة زر إكمال الرحلة إذا كان العقد لديه رحلة
                                $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                                if ($travelHistory && $travelHistory->status !== 'completed' && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                                    return '<button class="btn btn-success btn-sm complete-trip-btn btn-action" data-trip-id="' . $travelHistory->id . '" title="إكمال الرحلة">
                                        <i class="fas fa-check me-1"></i>إكمال الرحلة
                                    </button>';
                                }
                                break;
                            default:
                                break;
                        }

                        if ($hasPermission) {
                            $lastContractStatusLog = $row->getLastContractStatusLog();
                            return '<button class="btn btn-success btn-sm update-status-btn btn-action" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-next-status="' . $nextStatus . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '" title="' . $buttonText . '">
                                <i class="fas fa-arrow-up me-1"></i>' . $buttonText . '
                            </button>';
                        } else {
                            return '<span class="text-muted">لا توجد صلاحية</span>';
                        }
                    }
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button"  aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // زر عرض العقد
                    $actions .= '<li><a class="dropdown-item" href="' . route('contracts.details', $row->id) . '" target="_blank">
                                    <i class="fas fa-eye"></i> عرض العقد
                                </a></li>';

                    // زر الطباعة
                    $actions .= '<li><a class="dropdown-item" href="' . route('contracts.details', $row->id) . '" target="_blank">
                                    <i class="fas fa-print"></i> طباعة العقد
                                </a></li>';

                    // منع التعديل والحذف للعقود المكتملة والمرتجعة والتي في الإيواء
                    if ($row->status !== ContractStatusEnum::Completed->value &&
                        $row->status !== ContractStatusEnum::Back->value &&
                        $row->status !== ContractStatusEnum::Shelter->value) {

                        // زر التعديل
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
                            $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . '.edit', $row->id) . '">
                                            <i class="fas fa-edit"></i> تعديل العقد
                                        </a></li>';
                        }

                        // زر الحذف
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                            $actions .= '<li><a class="dropdown-item" href="' . route($this->mainRoute . ".destroy", $row->id) . '" onclick="return confirm(\'هل أنت متأكد من الحذف؟\')">
                                            <i class="fas fa-trash"></i> حذف العقد
                                        </a></li>';
                        }
                    } else {
                        // للعقود المكتملة والمرتجعة والتي في الإيواء - إضافة رسالة توضيحية
                        if ($row->status === ContractStatusEnum::Completed->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-success">العقد مكتمل - لا يمكن التعديل</span>
                                        </span></li>';
                        } elseif ($row->status === ContractStatusEnum::Shelter->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-info">العقد في الإيواء - لا يمكن التعديل</span>
                                        </span></li>';
                        } else {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-danger">العقد مرتجع - لا يمكن التعديل</span>
                                        </span></li>';
                        }
                    }

                    // زر إكمال الرحلة - يظهر فقط للعقود التي لديها رحلة فعلية وليس في مرحلة التذكرة
                    $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                    if ($travelHistory && @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                        // التأكد من أن العقد ليس في مرحلة التذكرة وأن الرحلة موجودة ولم تكتمل
                        if ($row->status !== ContractStatusEnum::Tazker->value &&
                            $travelHistory->status !== 'completed') {
                            $actions .= '<li><button class="dropdown-item complete-trip-btn" data-trip-id="' . $travelHistory->id . '">
                                            <i class="fas fa-check"></i> إكمال الرحلة
                                        </button></li>';
                        }
                    }

                    // زر إضافة رحلة - يظهر للعقود في مرحلة التذكرة التي لا تحتوي على رحلة
                    if ($row->status === ContractStatusEnum::Tazker->value &&
                        !$travelHistory &&
                        @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL)) {
                        $actions .= '<li><button class="dropdown-item add-travel-btn" data-contract-id="' . $row->id . '">
                                        <i class="fas fa-plane"></i> إضافة رحلة
                                    </button></li>';
                    }

                    // زر الإرجاع - لا يظهر للعقود المكتملة أو التي في الإيواء
                    if ($row->status !== ContractStatusEnum::Completed->value && $row->status !== ContractStatusEnum::Shelter->value) {
                        if ($row->status == ContractStatusEnum::Back->value) {
                            $actions .= '<li><span class="dropdown-item disabled">
                                            <span class="badge bg-warning">مرتجع</span>
                                        </span></li>';
                        } else {
                            // التحقق من صلاحية إرجاع العقد
                            if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
                                $actions .= '<li><button class="dropdown-item backContract" data-row-id="' . $row->id . '">
                                                <i class="fas fa-undo"></i> إرجاع العقد
                                            </button></li>';
                            }
                        }
                    }

                    // زر إلغاء العقد - لا يظهر للعقود المكتملة أو المرتجعة أو التي في الإيواء
                    if ($row->status !== ContractStatusEnum::Completed->value &&
                        $row->status !== ContractStatusEnum::Back->value &&
                        $row->status !== ContractStatusEnum::Shelter->value) {
                        if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                            $actions .= '<li><button class="dropdown-item cancel-contract-btn" data-contract-id="' . $row->id . '" data-contract-number="' . $row->id . '" data-client-name="' . ($row->user ? $row->user->name : 'غير محدد') . '" data-maid-name="' . ($row->maid ? $row->maid->name : 'غير محدد') . '">
                                            <i class="fas fa-times-circle"></i> إلغاء العقد
                                        </button></li>';
                        }
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->escapeColumns([])
                ->make(true);
        }

        // جلب خيارات الفلترة
        $filterOptions = $this->getFilterOptions();

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        $data["status"] = $status ?? $request->get('status', null);
        $data["filterOptions"] = $filterOptions;
        return view($this->folderPath . '.index', $data);
    }


    public function details($id)
    {
        // التحقق من صلاحية عرض تفاصيل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_VIEW)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لعرض تفاصيل العقد'
            ], 403);
        }

        $contract = Contract::with([
            'user',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'info',
            'contract_status_logs'
        ])->findOrFail($id);

        // إضافة علاقة order فقط إذا كان order_id موجود
        if ($contract->order_id) {
            $contract->load(['order.details.admin']);
        }

        $info = ContractInfo::where('contract_id', $contract->id)->first();

        // الحصول على بيانات المسوق
        $marketer = null;
        try {
            // أولاً: التحقق من marketer_id في العقد مباشرة
            if ($contract->marketer_id) {
                $marketer = \App\Models\auth\Admin::find($contract->marketer_id);
            }
            // ثانياً: التحقق من بيانات الطلب إذا لم يوجد marketer_id
            elseif ($contract->order && $contract->order->details) {
                $marketer = $contract->order->details->admin;
            }
        } catch (\Exception $e) {
            \Log::warning('Could not load marketer for contract: ' . $contract->id, [
                'error' => $e->getMessage(),
                'contract_marketer_id' => $contract->marketer_id,
                'contract_order_id' => $contract->order_id
            ]);
        }

        //        return $contract;
        return view('admin.ingaz.RecruitmentContract.details', compact('contract', 'info', 'marketer'));
    }

    public function create()
    {
        // التحقق من صلاحية إنشاء عقد جديد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        $orderId = request('orderId');
        \Log::info('Loading create page with orderId: ' . $orderId);

        // Load the selected order with all necessary relationships
        $selectedOrder = null;
        if ($orderId) {
            $selectedOrder = Order::with([
                'user.nationality',
                'maid.nationality',
                'maid.religion',
                'jobType.translations'
            ])->find($orderId);
            \Log::info('Selected order loaded:', ['order' => $selectedOrder ? $selectedOrder->toArray() : null]);
        }

        // Get new orders with relationships
        $orders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'jobType.translations'
        ])
        ->where('status', 'new')
        ->get();

        // Get ended orders with relationships
        $endedOrders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'jobType.translations'
        ])
        ->where('status', 'ended')
        ->get();

        // Get all available maids with relationships - استبعاد العاملات التي لها عقود نشطة
        $all_available_maids = Maid::with([
            'nationality',
            'religion',
            'jobTitles'
        ])
        ->where('available', true)
        ->where('status', 'available')
        ->where('is_show', true)
        ->where('available_at', '<=', now()->toDateString())
        ->whereNotNull('name')
        ->where('name', '!=', '')
        // استبعاد العاملات التي لها عقود نشطة أو معلقة
        ->whereDoesntHave('contracts', function($q) {
            $q->whereNotIn('status', ['back', 'closed', 'completed']);
        })
        // استبعاد العاملات التي لها طلبات نشطة
        ->whereDoesntHave('orders', function($q) {
            $q->whereIn('status', ['pending', 'approved', 'in_progress']);
        })
        ->get();

        // Get existing clients for new contract
        $existing_clients = User::with('nationality')->get();

        // If we have a selected order and it's not in the new orders list, add it
        if ($selectedOrder && !$orders->contains('id', $selectedOrder->id)) {
            $orders->prepend($selectedOrder);
        }

        $countries = Country::active()->ordered()->get();

        // Get current user info
        $currentUser = Auth::guard('admin')->user();

        return view('admin.ingaz.RecruitmentContract.create', compact('orders', 'endedOrders', 'countries', 'all_available_maids', 'selectedOrder', 'orderId', 'existing_clients', 'currentUser'));
    }


    public function store(StoreContractRequest $request)
    {
        // التحقق من صلاحية إنشاء العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CREATE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإنشاء عقد جديد'
            ], 403);
        }

        \Log::info('Recruitment Contract Creation Request:', $request->all());
        \Log::info('Request validation:', [
            'order_id' => $request->order_id,
            'client_type' => $request->client_type,
            'contract_date' => $request->contract_date,
            'msaned_number' => $request->msaned_number,
            'visa_number' => $request->visa_number,
            'contract_duration' => $request->contract_duration,
            'client_national_num' => $request->client_national_num,
            'new_client_national_num' => $request->new_client_national_num,
            'client_birthdate' => $request->client_birthdate,
            'new_client_birthdate' => $request->new_client_birthdate,
            'client_address' => $request->address,
            'new_client_address' => $request->new_client_address,
            'client_phone2' => $request->phone2,
            'new_client_phone2' => $request->new_client_phone2,
            'client_city' => $request->city,
            'new_client_city' => $request->new_client_city
        ]);

        try {
            DB::beginTransaction();

            // دعم حالة عقد بدون عاملة أو تأجيل اختيار العاملة
            $maidId = $request->maid_id ?? $request->new_maid_id;
            $isNoMaid = ($maidId === 'no_maid');
            $isMaidSelectionPostponed = $request->maid_selection_postponed == '1';

            // تحديد حالة العقد
            $contractStatus = ContractStatusEnum::New->value;
            if ($isNoMaid || $isMaidSelectionPostponed) {
                $contractStatus = ContractStatusEnum::PendingMaidSelection->value;
            }

            $contractData = [
                'status' => $contractStatus,
                'maid_id' => ($isNoMaid || $isMaidSelectionPostponed) ? null : $maidId,
                'required_maid_nationality_id' => $request->required_maid_nationality, // حفظ الجنسية المفضلة
            ];

            if ($request->order_id) {
                $order = Order::findOrFail($request->order_id);
                \Log::info('Found order:', ['order' => $order->toArray()]);

                // تحديث بيانات المستخدم من الطلب المسبق
                $user = $order->user;
                if ($user) {
                    \Log::info('Updating user from order - Request data:', [
                        'new_client_national_num' => $request->new_client_national_num,
                        'client_national_num' => $request->client_national_num,
                        'new_client_birthdate' => $request->new_client_birthdate,
                        'client_birthdate' => $request->client_birthdate,
                        'new_client_address' => $request->new_client_address,
                        'address' => $request->address,
                        'new_client_phone2' => $request->new_client_phone2,
                        'phone2' => $request->phone2,
                        'new_client_city' => $request->new_client_city,
                        'city' => $request->city,
                        'user_current_national_num' => $user->national_num
                    ]);

                    $user->update([
                        'national_num' => $request->new_client_national_num ?? $request->client_national_num ?? $user->national_num,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate ?? $user->birthdate,
                        'address' => $request->new_client_address ?? $request->address ?? $user->address,
                        'phone2' => $request->new_client_phone2 ?? $request->phone2 ?? $user->phone2,
                        'city' => $request->new_client_city ?? $request->city ?? $user->city,
                        'beds_num' => $request->new_client_beds_num ?? $request->beds_num ?? $user->beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->family_num ?? $user->family_num,
                        'children' => $request->new_client_children ?? $request->children ?? $user->children,
                        'job' => $request->new_client_job ?? $request->job ?? $user->job,
                    ]);
                    \Log::info('Updated user from order:', ['user' => $user->toArray()]);
                }

                $contractData['user_id'] = $order->user_id;
                $contractData['maid_id'] = $isNoMaid ? null : $order->maid_id;
                $contractData['order_id'] = $order->id; // إضافة order_id للعقد
                $contractData['marketer_id'] = $request->marketer_id ?: Auth::guard('admin')->id(); // أخذ المسوق من الطلب أو المستخدم الحالي

                \Log::info('Contract data from order:', $contractData);
            } else {
                // Handle new contract without existing order
                \Log::info('Creating/updating user for new contract - Request data:', [
                    'client_phone' => $request->client_phone,
                    'client_name' => $request->client_name,
                    'new_client_national_num' => $request->new_client_national_num,
                    'client_national_num' => $request->client_national_num,
                    'new_client_birthdate' => $request->new_client_birthdate,
                    'client_birthdate' => $request->client_birthdate,
                    'new_client_address' => $request->new_client_address,
                    'client_address' => $request->client_address,
                    'new_client_phone2' => $request->new_client_phone2,
                    'client_phone2' => $request->client_phone2,
                    'new_client_city' => $request->new_client_city,
                    'client_city' => $request->client_city
                ]);

                $user = User::where('phone', $request->client_phone)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'phone' => $request->client_phone,
                        'password' => bcrypt('123456'), // Default password
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->new_client_national_num ?? $request->client_national_num,
                        'email' => $request->new_client_email ?? $request->client_email,
                        'phone2' => $request->new_client_phone2 ?? $request->client_phone2,
                        'city' => $request->new_client_city ?? $request->client_city,
                        'beds_num' => $request->new_client_beds_num ?? $request->client_beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->client_family_num,
                        'children' => $request->new_client_children ?? $request->client_children,
                        'job' => $request->new_client_job ?? $request->client_job,
                        'address' => $request->new_client_address ?? $request->client_address,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate,
                    ]);
                    \Log::info('Created new user:', ['user' => $user->toArray()]);
                } else {
                    // Update existing user with new data
                    $user->update([
                        'name' => $request->client_name,
                        'phone_code' => '+966',
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->new_client_national_num ?? $request->client_national_num,
                        'email' => $request->new_client_email ?? $request->client_email,
                        'phone2' => $request->new_client_phone2 ?? $request->client_phone2,
                        'city' => $request->new_client_city ?? $request->client_city,
                        'beds_num' => $request->new_client_beds_num ?? $request->client_beds_num,
                        'family_num' => $request->new_client_family_num ?? $request->client_family_num,
                        'children' => $request->new_client_children ?? $request->client_children,
                        'job' => $request->new_client_job ?? $request->client_job,
                        'address' => $request->new_client_address ?? $request->client_address,
                        'birthdate' => $request->new_client_birthdate ?? $request->client_birthdate,
                        'password' => bcrypt('123456'), // Update password
                    ]);
                    \Log::info('Updated existing user:', ['user' => $user->toArray()]);
                }
                $contractData['user_id'] = $user->id;
                $contractData['marketer_id'] = $request->marketer_id ?: Auth::guard('admin')->id(); // إضافة المسوق تلقائياً
            }

            \Log::info('Creating contract with data:', $contractData);
            \Log::info('Final user data before contract creation:', [
                'user_id' => $contractData['user_id'],
                'user_national_num' => $user->national_num,
                'user_birthdate' => $user->birthdate,
                'user_address' => $user->address,
                'user_phone2' => $user->phone2,
                'user_city' => $user->city
            ]);

            $contract = Contract::create($contractData);
            \Log::info('Contract created:', ['contract' => $contract->toArray()]);
            \Log::info('Contract user data after creation:', [
                'user_id' => $contract->user_id,
                'order_id' => $contract->order_id,
                'user_national_num' => $contract->user->national_num,
                'user_birthdate' => $contract->user->birthdate,
                'user_address' => $contract->user->address,
                'user_phone2' => $contract->user->phone2,
                'user_city' => $contract->user->city
            ]);

            // حفظ معلومات العقد
            $contractInfo = [
                'contract_date' => $request->contract_date,
                'msaned_number' => $request->msaned_number,
                'tafwed_number' => $request->tafwed_number,
                'visa_number' => $request->visa_number,
                'visa_milad_date' => $request->visa_milad_date,
                'visa_hijry_date' => $request->visa_hijry_date,
                'contract_duration' => $request->contract_duration,
            ];

            \Log::info('Creating contract info:', $contractInfo);

            $contract->info()->create($contractInfo);
            \Log::info('Contract info created');

            // حفظ سجل حالة العقد
            $contract->contract_status_logs()->create(['status' => $contractStatus]);
            \Log::info('Contract status log created');

            // تحديث حالة الطلب إذا كان العقد من طلب
            if ($request->order_id) {
                $order->update(['status' => OrderEnum::Completed->value]);
                \Log::info('Order status updated to completed');
            }

            // تحديث حالة العاملات تلقائياً
            if ($contract->maid_id && !$isNoMaid && !$isMaidSelectionPostponed) {
                $maid = Maid::find($contract->maid_id);
                if ($maid) {
                    $maid->update([
                        'available' => false,
                        'status' => 'reserved'
                    ]);
                    \Log::info('Maid status updated to reserved', [
                        'maid_id' => $maid->id,
                        'maid_name' => $maid->name,
                        'contract_id' => $contract->id
                    ]);
                }
            }

            DB::commit();

            \Log::info('Contract created successfully', ['contract_id' => $contract->id]);

            $successMessage = 'تم إنشاء العقد بنجاح';
            if ($isNoMaid || $isMaidSelectionPostponed) {
                $successMessage = 'تم إنشاء العقد بنجاح. يمكنك إضافة عاملة لاحقاً من صفحة العقود.';
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'contract' => $contract->load(['user', 'maid', 'info'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'order_id' => $request->order_id,
                'client_type' => $request->client_type
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }


    public function show($id, Request $request)
    {
        try {
        $row = Contract::find($id);

            // التحقق من وجود العقد
            if (!$row) {
                \Log::warning('Contract not found', ['id' => $id, 'request' => $request->all()]);
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من أن العقد من نوع الاستقدام
            // تم إزالة هذا التحقق لأن جميع العقود في هذا Controller هي عقود استقدام
            // if ($row->type !== 'recruitment') {
            //     \Log::warning('Contract is not recruitment type', ['id' => $id, 'type' => $row->type]);
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'هذا العقد ليس من نوع الاستقدام'
            //     ], 400);
            // }

            // إذا كان الطلب لتحديث الحالة
            if ($request->has('status') && $request->has('next_status')) {
                $currentStatus = $request->status;
                $nextStatus = $request->next_status;
                $statusDate = $request->date ?? now()->format('Y-m-d');

        // التحقق من الصلاحيات حسب حالة العقد الحالية
        $hasPermission = false;
        $permissionRequired = '';

                switch ($currentStatus) {
            case ContractStatusEnum::New->value:
                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED;
                if (@AdminGuardHasPerm($permissionRequired)) {
                    $hasPermission = true;
                }
                break;
            case ContractStatusEnum::Msaned->value:
                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED;
                if (@AdminGuardHasPerm($permissionRequired)) {
                    $hasPermission = true;
                }
                break;
            case ContractStatusEnum::Tafwed->value:
                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ;
                if (@AdminGuardHasPerm($permissionRequired)) {
                    $hasPermission = true;
                }
                break;
            case ContractStatusEnum::Taffez->value:
                $permissionRequired = \App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER;
                if (@AdminGuardHasPerm($permissionRequired)) {
                    $hasPermission = true;
                }
                break;
                    case ContractStatusEnum::Arrival->value:
                // التحقق من صلاحية إغلاق العقد بعد الضمان
                if (@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY)) {
                    $hasPermission = true;
                }
                break;
            default:
                break;
        }

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                        'message' => 'ليس لديك صلاحية لتغيير حالة العقد من ' . getContractStatus($currentStatus) . ' إلى المرحلة التالية'
            ], 403);
        }

                // تحديث حالة العقد
                $row->status = $nextStatus;
        $row->save();

                // حفظ سجل حالة العقد
        $row->contract_status_logs()->create([
                    'status' => $nextStatus,
                    'date' => $statusDate
        ]);

        return response()->json([
            'success' => true,
                    'message' => 'تم تحديث حالة العقد بنجاح من ' . getContractStatus($currentStatus) . ' إلى ' . getContractStatus($nextStatus)
                ]);
            }

            // إذا كان الطلب لعرض معلومات العقد فقط
            return response()->json([
                'success' => true,
                'message' => 'تم تحميل معلومات العقد بنجاح',
                'contract' => $row->load(['user', 'maid', 'info'])
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in show contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة الطلب: ' . $e->getMessage()
            ], 500);
        }
    }

    public function back($id)
    {
        try {
        // التحقق من صلاحية إرجاع العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_RETURN)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لإرجاع العقد'
            ], 403);
        }

        $contract = Contract::find($id);
        if (!$contract) {
                \Log::warning('Contract not found in back function', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        // منع إرجاع العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إرجاع العقد الذي في الإيواء. يجب حذفه من الإيواء أولاً'
            ], 400);
        }

        // منع إرجاع العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إرجاع العقد المكتمل'
            ], 400);
        }

        $newStatus = contractBackStatus($contract->status);
        $contract->update(['status' => $newStatus->value]);
        $contract->contract_status_logs()->create(['status' => $newStatus->value]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرجاع العقد بنجاح إلى ' . getContractStatus($newStatus->value)
        ]);

        } catch (\Exception $e) {
            \Log::error('Error in back contract', [
                'id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرجاع العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // التحقق من صلاحية تعديل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية لتعديل العقد');
        }

        $contract = Contract::with(['user', 'maid.nationality', 'maid.religion', 'maid.jobTitles', 'info'])->find($id);

        if (!$contract) {
            return redirect()->back()->with('error', 'العقد غير موجود');
        }

        // منع تعديل العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return redirect()->back()->with('error', 'لا يمكن تعديل العقد المكتمل');
        }

        // منع تعديل العقود المرتجعة
        if ($contract->status === ContractStatusEnum::Back->value) {
            return redirect()->back()->with('error', 'لا يمكن تعديل العقد المرتجع');
        }

        // منع تعديل العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return redirect()->back()->with('error', 'لا يمكن تعديل العقد الذي في الإيواء');
        }

        $countries = Country::all();

        return view('admin.ingaz.RecruitmentContract.edit', compact('contract', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // التحقق من صلاحية تعديل العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EDIT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل العقد'
            ], 403);
        }

        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        // منع تحديث حالة العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة العقد المكتمل'
            ], 400);
        }

        // منع تحديث حالة العقود المرتجعة
        if ($contract->status === ContractStatusEnum::Back->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة العقد المرتجع'
            ], 400);
        }

        // منع تحديث حالة العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث حالة العقد الذي في الإيواء'
            ], 400);
        }

        try {
            // تحديث بيانات العميل
            if ($contract->user) {
                $contract->user->update([
                    'name' => $request->client_name,
                    'phone' => $request->client_phone,
                    'email' => $request->client_email,
                    'nationality_id' => $request->client_nationality_id,
                    'national_num' => $request->client_national_num,
                    'address' => $request->client_address,
                ]);
            }

            // تحديث بيانات العاملة
            if ($contract->maid) {
                $contract->maid->update([
                    'name' => $request->maid_name,
                    'passport_num' => $request->maid_passport,
                    'salary' => $request->maid_salary,
                ]);
            }

            // تحديث معلومات العقد
            if ($contract->info) {
                $contract->info->update([
                    'contract_date' => $request->contract_date,
                    'msaned_number' => $request->msaned_number,
                    'visa_number' => $request->visa_number,
                    'tafwed_number' => $request->tafwed_number,
                    'visa_milad_date' => $request->visa_milad_date,
                    'visa_hijry_date' => $request->visa_hijry_date,
                    'contract_duration' => $request->contract_duration,
                ]);
            }

            // تحديث حالة العقد
            $contract->update([
                'status' => $request->status
            ]);

            // تحديث حالة العاملات تلقائياً
            if ($contract->maid_id) {
                $maid = Maid::find($contract->maid_id);
                if ($maid) {
                    // التحقق من حالة العقد
                    $isActiveContract = !in_array($request->status, ['back', 'closed', 'completed']);

                    if ($isActiveContract) {
                        // العقد نشط، العاملة يجب أن تكون محجوزة
                        $maid->update([
                            'available' => false,
                            'status' => 'reserved'
                        ]);
                    } else {
                        // العقد غير نشط، التحقق من وجود عقود أخرى نشطة
                        $activeContracts = $maid->contracts()->whereNotIn('status', ['back', 'closed', 'completed'])->get();
                        $activeOrders = $maid->orders()->whereIn('status', ['pending', 'approved', 'in_progress'])->get();

                        if ($activeContracts->count() == 0 && $activeOrders->count() == 0) {
                            // لا توجد عقود أو طلبات نشطة، العاملة يمكن أن تكون متاحة
                            $maid->update([
                                'available' => true,
                                'status' => 'available'
                            ]);
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        // التحقق من صلاحية حذف العقد
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف العقد'
            ], 403);
        }

        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'العقد غير موجود'
            ], 404);
        }

        // منع حذف العقود المكتملة
        if ($contract->status === ContractStatusEnum::Completed->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد المكتمل'
            ], 400);
        }

        // منع حذف العقود المرتجعة
        if ($contract->status === ContractStatusEnum::Back->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد المرتجع'
            ], 400);
        }

        // منع حذف العقود في الإيواء
        if ($contract->status === ContractStatusEnum::Shelter->value) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد الذي في الإيواء'
            ], 400);
        }

        $contract->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم حذف العقد بنجاح'
        ]);
    }

    public function getMaidByNationality($nationalityId)
    {
        $maids = Maid::where('nationality_id', $nationalityId)->get();
        return response()->json($maids->toArray());
    }

    public function getClientDetails($id)
    {
        $client = User::with('nationality')->find($id);
        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        return response()->json($client);
    }

    public function exportPdf()
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة عقود الاستقدام</title>
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
                <h1>قائمة عقود الاستقدام</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>رقم هاتف العميل</th>
                        <th>العاملة</th>
                        <th>رقم جواز العامل</th>
                        <th>تاريخ العقد</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ النهاية</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($contracts as $contract) {
            $html .= '<tr>
                <td>' . $contract->id . '</td>
                <td>' . ($contract->user ? $contract->user->name : '') . '</td>
                <td>' . ($contract->user ? getPhoneWithTag($contract->user->phone_code . $contract->user->phone) : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->name : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->passport_num : '') . '</td>
                <td>' . ($contract->info ? $contract->info->contract_date : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_from : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_to : '') . '</td>
                <td>' . getEnumValue('contract_statuses', $contract->status) . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="contracts.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        // التحقق من صلاحية تصدير العقود
        if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_EXPORT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتصدير العقود'
            ], 403);
        }

        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم العقد',
            'العميل',
            'رقم هاتف العميل',
            'العاملة',
            'رقم جواز العامل',
            'تاريخ العقد',
            'تاريخ البداية',
            'تاريخ النهاية',
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
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($contracts as $contract) {
            $sheet->setCellValue('A' . $row, $contract->id);
            $sheet->setCellValue('B' . $row, $contract->user ? $contract->user->name : '');
            $sheet->setCellValue('C' . $row, $contract->user ? $contract->user->phone_code . $contract->user->phone : '');
            $sheet->setCellValue('D' . $row, $contract->maid ? $contract->maid->name : '');
            $sheet->setCellValue('E' . $row, $contract->maid ? $contract->maid->passport_num : '');
            $sheet->setCellValue('F' . $row, $contract->info ? $contract->info->contract_date : '');
            $sheet->setCellValue('G' . $row, $contract->info ? $contract->info->ingaz_from : '');
            $sheet->setCellValue('H' . $row, $contract->info ? $contract->info->ingaz_to : '');
            $sheet->setCellValue('I' . $row, getEnumValue('contract_statuses', $contract->status));
            $row++;
        }

        // Auto-size columns
        foreach(range('A','I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="contracts.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'contracts.xlsx', $headers)->deleteFileAfterSend(true);
    }

    public function completeTrip($tripId)
    {
        try {
            // التحقق من صلاحية إكمال الرحلة
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإكمال الرحلة'
                ], 403);
            }

            $travelHistory = TravelHistory::find($tripId);
            if (!$travelHistory) {
                return response()->json([
                    'success' => false,
                    'message' => 'الرحلة غير موجودة'
                ], 404);
            }

            // البحث عن العقد المرتبط بالرحلة
            $contract = Contract::find($travelHistory->order_id);
            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد المرتبط بالرحلة غير موجود'
                ], 404);
            }

            DB::beginTransaction();

            try {
                // تحديث حالة الرحلة إلى مكتملة
                $travelHistory->update(['status' => 'completed']);

                // تحديث حالة العقد إلى مكتمل
                $contract->update(['status' => 'completed']);

                // حساب حالة الضمان تلقائياً
                $contract->calculateWarrantyStatus();

                // إنشاء سجل حالة العقد
                $contract->contract_status_logs()->create([
                    'status' => 'completed',
                    'date' => now(),
                    'notes' => 'تم إكمال الرحلة وتحديث حالة العقد إلى مكتمل'
                ]);

                DB::commit();

                \Log::info('Trip completed and contract status updated', [
                    'trip_id' => $tripId,
                    'contract_id' => $contract->id,
                    'warranty_status' => $contract->warranty_status
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إكمال الرحلة وتحديث حالة العقد إلى مكتمل بنجاح'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error in completeTrip', [
                'tripId' => $tripId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إكمال الرحلة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addTravel(Request $request)
    {
        try {
            // التحقق من صلاحية إضافة رحلة
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_ADD_TRAVEL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإضافة رحلة'
                ], 403);
            }

            $contractId = $request->contract_id;
            $contract = Contract::find($contractId);

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من عدم وجود رحلة نشطة للعقد
            $existingTravel = TravelHistory::where('order_id', $contractId)->first();
            if ($existingTravel) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد رحلة نشطة بالفعل لهذا العقد'
                ], 400);
            }

            // إنشاء رحلة جديدة
            TravelHistory::create([
                'order_id' => $contractId,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الرحلة بنجاح'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in addTravel', [
                'contractId' => $request->contract_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الرحلة: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $rowId = $request->row_id;
            $nextStatus = $request->next_status;
            $date = $request->date;

            $contract = Contract::find($rowId);
            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // منع تحديث حالة العقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تحديث حالة العقد المكتمل'
                ], 400);
            }

            // منع تحديث حالة العقود في الإيواء
            if ($contract->status === ContractStatusEnum::Shelter->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تحديث حالة العقد الذي في الإيواء'
                ], 400);
            }

            // التحقق من الصلاحيات حسب الحالة الجديدة
            $hasPermission = false;
            switch ($nextStatus) {
                case ContractStatusEnum::Msaned->value:
                    $hasPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED);
                    break;
                case ContractStatusEnum::Tafwed->value:
                    $hasPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED);
                    break;
                case ContractStatusEnum::Taffez->value:
                    $hasPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ);
                    break;
                case ContractStatusEnum::Tazker->value:
                    $hasPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER);
                    break;
                case ContractStatusEnum::Closed->value:
                    $hasPermission = @AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY);
                    break;
                default:
                    $hasPermission = false;
                    break;
            }

            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لتحديث الحالة'
                ], 403);
            }

            // تحديث حالة العقد
            $contract->update(['status' => $nextStatus]);

            // إنشاء سجل حالة العقد
            $contract->contract_status_logs()->create([
                'status' => $nextStatus,
                'date' => $date ?: now(),
                'notes' => 'تم تحديث الحالة من ' . getContractStatus($contract->getOriginal('status')) . ' إلى ' . getContractStatus($nextStatus)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح إلى ' . getContractStatus($nextStatus)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in updateStatus', [
                'rowId' => $request->row_id,
                'nextStatus' => $request->next_status,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الحالة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب العاملات المتاحة
     */
    public function getAvailableMaids(Request $request)
    {
        \Log::info('getAvailableMaids called', ['request' => $request->all()]);

        try {
            $query = Maid::with(['nationality'])
                ->where('available', true)
                ->where('status', 'available') // التأكد من أن الحالة متاحة
                ->whereDoesntHave('contracts', function($q) {
                    $q->whereIn('status', [
                        'new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'
                    ]);
                })
                ->whereDoesntHave('orders', function($q) {
                    $q->whereIn('status', [
                        'new', 'under_process', 'contracted'
                    ]);
                });

            \Log::info('Query built', ['sql' => $query->toSql()]);

            // فلترة حسب الجنسية
            if ($request->has('nationality_id') && $request->nationality_id) {
                $query->where('nationality_id', $request->nationality_id);
            }

            // فلترة حسب الديانة
            if ($request->has('religion_id') && $request->religion_id) {
                $query->where('religion_id', $request->religion_id);
            }

            // البحث بالاسم أو رقم الجواز
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('passport_num', 'LIKE', "%{$search}%")
                      ->orWhere('id_number', 'LIKE', "%{$search}%");
                });
            }

            $availableMaids = $query->latest()->paginate(20)->withQueryString();

            // Log debug info
            \Log::info('نتائج البحث عن العاملات:', [
                'count' => $availableMaids->count(),
                'search' => $request->search,
                'nationality_id' => $request->nationality_id,
                'religion_id' => $request->religion_id,
                'job_title_id' => $request->job_title_id,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            // إضافة رسالة تحذير إذا لم توجد عاملات متاحة
            if ($availableMaids->count() === 0) {
                $warningMessage = 'لا توجد عاملات متاحة حالياً';

                if ($request->filled('search') || $request->filled('nationality_id') || $request->filled('religion_id') || $request->filled('job_title_id')) {
                    $warningMessage .= ' - جرب تغيير الفلاتر أو إعادة تعيين البحث';
                } else {
                    $warningMessage .= ' - جميع العاملات إما محجوزة أو غير متاحة';
                }

                session()->flash('warning', $warningMessage);
            }

            // تحويل البيانات للعرض
            $maidsData = $availableMaids->map(function($maid) {
                return [
                    'id' => $maid->id,
                    'name' => $maid->name ?? 'غير محدد',
                    'passport_num' => $maid->passport_num ?? 'غير محدد',
                    'nationality' => $maid->nationality->sub_title ?? 'غير محدد'
                ];
            });

            \Log::info('Available maids loaded', [
                'count' => $maidsData->count(),
                'maids' => $maidsData->toArray()
            ]);

            return response()->json([
                'success' => true,
                'maids' => $maidsData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getAvailableMaids', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العاملات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب خيارات الفلترة
     */
    public function getFilterOptions()
    {
        // استخدام cache لتسريع التحميل
        return \Cache::remember('recruitment_contract_filter_options', 300, function () { // 5 دقائق
            return [
                'nationalities' => \App\Models\ingaz\Nationality::select('id')->with('translations:nationality_id,sub_title')->get(),
                'religions' => \App\Models\ingaz\Religion::select('id')->with('translations:religion_id,title')->get(),
                'offices' => \App\Models\RecruitmentOffices::select('id')->with('translations:rec_office_id,title')->get(),
                'marketers' => \App\Models\auth\Admin::select('id', 'name')->whereNull('deleted_at')->orderBy('name')->get(),
            ];
        });
    }

    /**
     * إضافة عاملة للعقد
     */
    public function addMaidToContract(Request $request, $contractId)
    {
        try {
            $contract = Contract::findOrFail($contractId);

            // التحقق من أن العقد في حالة بانتظار اختيار العاملة
            if ($contract->status !== ContractStatusEnum::PendingMaidSelection->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إضافة عاملة لهذا العقد'
                ], 400);
            }

            // التحقق من صحة البيانات
            $request->validate([
                'maid_id' => 'required|exists:maids,id'
            ]);

            // التحقق من أن العاملة متاحة
            $maid = Maid::find($request->maid_id);
            if (!$maid || !$maid->available) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة غير متاحة'
                ], 400);
            }

            // تحديث العقد
            $contract->update([
                'maid_id' => $request->maid_id,
                'status' => ContractStatusEnum::New->value
            ]);

            // تحديث حالة العاملات تلقائياً
            if ($maid) {
                $maid->update([
                    'available' => false,
                    'status' => 'reserved'
                ]);
            }

            // إضافة سجل حالة العقد
            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::New->value,
                'date' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة العاملة للعقد بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة العاملة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * صفحة اختيار عاملة للعقد
     */
    public function selectMaidForContract($contractId, Request $request)
    {
        try {
            $contract = Contract::with(['user', 'info'])->find($contractId);

            // التحقق من وجود العقد
            if (!$contract) {
                // البحث عن عقود بانتظار اختيار العاملة
                $pendingContracts = Contract::where('status', ContractStatusEnum::PendingMaidSelection->value)->pluck('id')->toArray();

                $errorMessage = 'العقد غير موجود';
                if (!empty($pendingContracts)) {
                    $errorMessage .= ' - العقود المتاحة بانتظار اختيار العاملة: ' . implode(', ', $pendingContracts);
                }

                return redirect()->route('RecruitmentContract.index')
                    ->with('error', $errorMessage);
            }

            // التحقق من أن العقد في حالة بانتظار اختيار العاملة
            if ($contract->status !== ContractStatusEnum::PendingMaidSelection->value) {
                return redirect()->route('RecruitmentContract.index')
                    ->with('error', 'لا يمكن إضافة عاملة لهذا العقد - الحالة الحالية: ' . $contract->status);
            }

            // جلب العاملات المتاحة مع الفلاتر
            $query = Maid::with(['nationality', 'religion', 'jobTitles'])
                ->where('available', true)
                ->where('status', 'available')
                ->where('is_show', true)
                ->where('available_at', '<=', now()->toDateString())
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->whereDoesntHave('contracts', function($q) {
                    $q->whereIn('status', ['active', 'pending']);
                })
                ->whereDoesntHave('orders', function($q) {
                    $q->whereIn('status', ['pending', 'approved', 'in_progress']);
                });

            // تطبيق الفلاتر
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('passport_num', 'LIKE', "%{$search}%")
                      ->orWhere('id_number', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('nationality_id')) {
                $query->where('nationality_id', $request->nationality_id);
            }

            if ($request->filled('religion_id')) {
                $query->where('religion_id', $request->religion_id);
            }

            if ($request->filled('job_title_id')) {
                $query->whereHas('jobTitles', function($q) use ($request) {
                    $q->where('job_title_id', $request->job_title_id);
                });
            }

            $availableMaids = $query->latest()->paginate(20)->withQueryString();

            // Log debug info
            \Log::info('نتائج البحث عن العاملات:', [
                'count' => $availableMaids->count(),
                'search' => $request->search,
                'nationality_id' => $request->nationality_id,
                'religion_id' => $request->religion_id,
                'job_title_id' => $request->job_title_id,
                'query' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);

            // إضافة رسالة تحذير إذا لم توجد عاملات متاحة
            if ($availableMaids->count() === 0) {
                $warningMessage = 'لا توجد عاملات متاحة حالياً';

                if ($request->filled('search') || $request->filled('nationality_id') || $request->filled('religion_id') || $request->filled('job_title_id')) {
                    $warningMessage .= ' - جرب تغيير الفلاتر أو إعادة تعيين البحث';
                } else {
                    $warningMessage .= ' - جميع العاملات إما محجوزة أو غير متاحة';
                }

                session()->flash('warning', $warningMessage);
            }

            // جلب قوائم الفلترة
            $nationalities = \App\Models\ingaz\Nationality::all();
            $religions = \App\Models\ingaz\Religion::all();
            $jobTitles = \App\Models\ingaz\JobTitle::all();



            return view('admin.ingaz.RecruitmentContract.select_maid', compact(
                'contract',
                'availableMaids',
                'nationalities',
                'religions',
                'jobTitles'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in selectMaidForContract', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'contractId' => $contractId,
                'request' => $request->all()
            ]);

            return redirect()->route('RecruitmentContract.index')
                ->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }

    public function statistics(Request $request)
    {
        try {
            $query = Contract::where('type', 'recruitment');

            // فلتر الحالة
            if ($request->filled('status')) {
                $query->where('status', $request->status);

                // فلتر حالة الضمان (فقط للعقود المكتملة)
                if ($request->status === 'completed' && $request->filled('warranty_status')) {
                    $query->where('warranty_status', $request->warranty_status);
                }
            }

            // فلتر التاريخ من
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            // فلتر التاريخ إلى
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // فلتر رقم جوال العميل
            if ($request->filled('client_phone')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->client_phone . '%');
                });
            }

            // فلتر اسم العميل
            if ($request->filled('client_name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // فلتر رقم جواز العاملة
            if ($request->filled('maid_passport')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->maid_passport . '%');
                });
            }

            // فلتر اسم العاملة
            if ($request->filled('maid_name')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->maid_name . '%');
                });
            }

            // فلتر رقم العقد
            if ($request->filled('contract_number')) {
                $query->where('id', 'like', '%' . $request->contract_number . '%');
            }

            // فلتر جنسية العاملة
            if ($request->filled('nationality')) {
                $query->whereHas('maid.nationality.translations', function($q) use ($request) {
                    $q->where('sub_title', $request->nationality);
                });
            }

            // فلتر ديانة العاملة
            if ($request->filled('religion')) {
                $query->whereHas('maid.religion.translations', function($q) use ($request) {
                    $q->where('title', $request->religion);
                });
            }

            // فلتر المكتب الخارجي
            if ($request->filled('office_id')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('office_id', $request->office_id);
                });
            }

            // فلتر مدينة العميل
            if ($request->filled('city')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('city', 'like', '%' . $request->city . '%');
                });
            }

            $total = $query->count();
            $active = $query->whereIn('status', [
                ContractStatusEnum::New->value,
                ContractStatusEnum::Msaned->value,
                ContractStatusEnum::Tafwed->value,
                ContractStatusEnum::Taffez->value,
                ContractStatusEnum::Tazker->value,
                ContractStatusEnum::Arrival->value
            ])->count();
            $pending = $query->where('status', ContractStatusEnum::PendingMaidSelection->value)->count();
            $completed = $query->where('status', ContractStatusEnum::Completed->value)->count();
            $returned = $query->where('status', ContractStatusEnum::Back->value)->count();
            $closed = $query->where('status', ContractStatusEnum::Completed->value)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'pending' => $pending,
                    'completed' => $completed,
                    'returned' => $returned,
                    'closed' => $closed
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in contract statistics:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحصائيات'
            ], 500);
        }
    }

    /**
     * تغيير العاملة في العقد
     */
    public function changeMaid(Request $request, $contractId)
    {
        try {
            // التحقق من الصلاحيات
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لتعديل العقود'
                ], 403);
            }

            // التحقق من وجود العقد
            $contract = Contract::with(['maid', 'user'])->findOrFail($contractId);

            // منع استبدال العاملة للعقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استبدال العاملة في العقد المكتمل'
                ], 400);
            }

            // منع استبدال العاملة للعقود المرتجعة
            if ($contract->status === ContractStatusEnum::Back->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن استبدال العاملة في العقد المرتجع'
                ], 400);
            }

            // التحقق من أن العقد من النوع الصحيح
            if (!in_array($contract->type, ['recruitment', 'maid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تغيير العاملة في هذا النوع من العقود'
                ], 400);
            }

            // التحقق من البيانات المطلوبة
            $request->validate([
                'new_maid_id' => 'required|exists:maids,id',
                'old_maid_status' => 'required|in:available,backout',
                'reason' => 'nullable|string|max:500'
            ]);

            $newMaidId = $request->new_maid_id;
            $oldMaidStatus = $request->old_maid_status;
            $reason = $request->reason;

            // التحقق من أن العاملة الجديدة متاحة
            $newMaid = Maid::findOrFail($newMaidId);

            // التحقق من أن العاملة الجديدة ليس لها عقود نشطة
            $hasActiveContracts = Contract::where('maid_id', $newMaidId)
                ->whereIn('status', [
                    ContractStatusEnum::New->value,
                    ContractStatusEnum::Msaned->value,
                    ContractStatusEnum::Tafwed->value,
                    ContractStatusEnum::Taffez->value,
                    ContractStatusEnum::Tazker->value,
                    ContractStatusEnum::Arrival->value
                ])
                ->exists();

            if ($hasActiveContracts) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة لها عقود نشطة ولا يمكن استخدامها'
                ], 400);
            }

            // التحقق من أن العاملة الجديدة ليس لها طلبات نشطة
            $hasActiveOrders = Order::where('maid_id', $newMaidId)
                ->whereIn('status', [
                    OrderEnum::New->value,
                    OrderEnum::UnderProcess->value,
                    OrderEnum::Contracted->value
                ])
                ->exists();

            if ($hasActiveOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'العاملة المختارة لها طلبات نشطة ولا يمكن استخدامها'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // حفظ العاملة القديمة
                $oldMaid = $contract->maid;
                $oldMaidId = $oldMaid->id;

                \Log::info('Changing maid in contract', [
                    'contract_id' => $contractId,
                    'old_maid_id' => $oldMaidId,
                    'new_maid_id' => $newMaidId,
                    'old_maid_status' => $oldMaidStatus,
                    'old_maid_current_status' => $oldMaid->status,
                    'old_maid_current_available' => $oldMaid->available
                ]);

                // تحديث حالة العاملة القديمة
                $oldMaid->update([
                    'status' => $oldMaidStatus,
                    'available' => ($oldMaidStatus === 'available') ? true : false
                ]);

                // إعادة تحميل البيانات للتأكد من التحديث
                $oldMaid->refresh();

                // إذا كانت العاملة أصبحت غير متاحة، أضفها لقائمة العاملات غير المتاحات
                if ($oldMaidStatus === 'backout') {
                    \Artisan::call('maids:add-to-unavailable', [
                        'maid-id' => $oldMaidId,
                        '--contract-id' => $contractId,
                        '--reason' => $reason ?? 'تم تغيير العاملة من عقد',
                        '--admin-id' => Auth::guard('admin')->id()
                    ]);
                }

                \Log::info('Old maid status updated', [
                    'old_maid_id' => $oldMaidId,
                    'new_status' => $oldMaid->status,
                    'new_available' => $oldMaid->available
                ]);

                // تحديث العقد بالعاملة الجديدة
                $contract->update([
                    'maid_id' => $newMaidId
                ]);

                // تحديث حالة العاملة الجديدة إلى محجوزة
                $newMaid->update([
                    'status' => 'reserved',
                    'available' => false
                ]);

                // إعادة تحميل البيانات للتأكد من التحديث
                $newMaid->refresh();

                \Log::info('New maid status updated', [
                    'new_maid_id' => $newMaidId,
                    'new_status' => $newMaid->status,
                    'new_available' => $newMaid->available
                ]);

                // تسجيل العملية في سجل التغييرات
                \Log::info('Maid changed in contract', [
                    'contract_id' => $contractId,
                    'old_maid_id' => $oldMaidId,
                    'new_maid_id' => $newMaidId,
                    'old_maid_status' => $oldMaidStatus,
                    'reason' => $reason,
                    'changed_by' => Auth::guard('admin')->id()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'تم تغيير العاملة بنجاح',
                    'data' => [
                        'contract_id' => $contractId,
                        'old_maid' => [
                            'id' => $oldMaid->id,
                            'name' => $oldMaid->name,
                            'status' => $oldMaid->status,
                            'available' => $oldMaid->available
                        ],
                        'new_maid' => [
                            'id' => $newMaid->id,
                            'name' => $newMaid->name,
                            'status' => $newMaid->status,
                            'available' => $newMaid->available
                        ]
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error changing maid in contract:', [
                'contract_id' => $contractId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير العاملة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض صفحة تغيير العاملة
     */
    public function showChangeMaid($contractId)
    {
        try {
            // التحقق من الصلاحيات
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_UPDATE)) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية لتعديل العقود');
            }

            $contract = Contract::with(['maid', 'user'])->findOrFail($contractId);

            // منع استبدال العاملة للعقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return redirect()->back()->with('error', 'لا يمكن استبدال العاملة في العقد المكتمل');
            }

            // منع استبدال العاملة للعقود المرتجعة
            if ($contract->status === ContractStatusEnum::Back->value) {
                return redirect()->back()->with('error', 'لا يمكن استبدال العاملة في العقد المرتجع');
            }

            $availableMaids = Maid::where('available', true)
                ->whereDoesntHave('contracts', function($q) {
                    $q->whereIn('status', [
                        ContractStatusEnum::New->value,
                        ContractStatusEnum::Msaned->value,
                        ContractStatusEnum::Tafwed->value,
                        ContractStatusEnum::Taffez->value,
                        ContractStatusEnum::Tazker->value,
                        ContractStatusEnum::Arrival->value
                    ]);
                })
                ->whereDoesntHave('orders', function($q) {
                    $q->whereIn('status', [
                        OrderEnum::New->value,
                        OrderEnum::UnderProcess->value,
                        OrderEnum::Contracted->value
                    ]);
                })
                ->get();

            return view($this->folderPath . '.change_maid', compact('contract', 'availableMaids'));

        } catch (\Exception $e) {
            \Log::error('Error showing change maid page:', [
                'contract_id' => $contractId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحميل الصفحة');
        }
    }

    /**
     * إلغاء العقد
     */
    public function cancel(Request $request)
    {
        try {
            // التحقق من الصلاحيات
            if (!@AdminGuardHasPerm(\App\NewPermKeys::REC_CONTRACTS_DELETE)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لإلغاء العقود'
                ], 403);
            }

            // التحقق من البيانات المطلوبة
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'cancel_reason' => 'required|string|max:500',
                'maid_status_after_cancel' => 'required|in:available,backout'
            ]);

            $contract = Contract::with(['maid', 'user'])->findOrFail($request->contract_id);

            // منع إلغاء العقود المكتملة
            if ($contract->status === ContractStatusEnum::Completed->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إلغاء العقد المكتمل'
                ], 400);
            }

            // منع إلغاء العقود المرتجعة
            if ($contract->status === ContractStatusEnum::Back->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد مسبقاً مرتجع'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // تحديث حالة العقد إلى مرتجع
                $contract->update([
                    'status' => ContractStatusEnum::Back->value
                ]);

                // إنشاء سجل حالة العقد
                $contract->contract_status_logs()->create([
                    'status' => ContractStatusEnum::Back->value,
                    'date' => now(),
                    'notes' => 'تم إلغاء العقد - السبب: ' . $request->cancel_reason
                ]);

                // تحديث حالة العاملة
                if ($contract->maid) {
                    $maid = $contract->maid;

                    if ($request->maid_status_after_cancel === 'available') {
                        // التحقق من عدم وجود عقود أخرى نشطة للعاملة
                        $activeContracts = $maid->contracts()
                            ->where('id', '!=', $contract->id)
                            ->whereNotIn('status', ['back', 'closed', 'completed'])
                            ->count();

                        $activeOrders = $maid->orders()
                            ->whereIn('status', ['pending', 'approved', 'in_progress'])
                            ->count();

                        if ($activeContracts === 0 && $activeOrders === 0) {
                            $maid->update([
                                'available' => true,
                                'status' => 'available'
                            ]);
                        }
                    } else {
                        // باك اوت
                        $maid->update([
                            'available' => false,
                            'status' => 'backout'
                        ]);
                    }
                }

                // تسجيل العملية
                \Log::info('Contract cancelled', [
                    'contract_id' => $contract->id,
                    'cancel_reason' => $request->cancel_reason,
                    'maid_status_after_cancel' => $request->maid_status_after_cancel,
                    'cancelled_by' => Auth::guard('admin')->id()
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء العقد بنجاح',
                    'contract' => $contract->load(['user', 'maid', 'info'])
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error cancelling contract:', [
                'contract_id' => $request->contract_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }
}

