<?php

namespace App\Http\Controllers\Api;

use App\Enums\auth\AdminTypes;
use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\CustomerResource as objResource;
use App\Models\auth\Admin as objModel;
use App\Models\Branch;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ApiCustomerController extends Controller
{
          /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // شكل كل موظف في data لم يتغيّر (branch_id/branch كانا موجودين أصلاً)،
        // حتى لا تتأثر المواقع الخارجية الأخرى المرتبطة بنفس الـ API.
        $customers = objModel::query()
            ->with('branch')
            ->where('admin_type', AdminTypes::CustomerService->value)
            ->activeForOperations()
            ->when($request->filled('branch_id'), function ($query) use ($request) {
                $query->where('branch_id', $request->integer('branch_id'));
            })
            ->get();

        // مفتاحان إضافيان فقط: قائمة الأفرع التي بها موظفو خدمة عملاء (بالترتيب
        // المعتمد) وحالة الوحدة، ليبني منها الموقع الخارجي تبويبات (الكل / فرع...).
        // الموقع الذي لا يعرفهما يتجاهلهما ويبقى كما هو.
        $branchesEnabled = SystemModuleManager::isEnabled('branches')
            && Schema::hasColumn('admins', 'branch_id');

        $usedBranchIds = $branchesEnabled
            ? $customers->pluck('branch_id')->filter()->unique()->all()
            : [];

        $branches = $usedBranchIds !== []
            ? Branch::query()->whereIn('id', $usedBranchIds)->ordered()->get(['id', 'name_ar', 'name_en'])
                ->map(fn (Branch $branch) => [
                    'id' => (int) $branch->id,
                    'name' => (string) $branch->name,
                ])
                ->values()
            : collect();

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => objResource::collection($customers),
            'branches_enabled' => $branchesEnabled,
            'branches' => $branches,
            'message' => __('api.All items'),
        ], 200, [], apiJsonEncodingOptions());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
       //
    }
}
