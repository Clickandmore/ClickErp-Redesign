<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;

class ApiClientDashboard extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        // Get user statistics
        $stats = [
            'orders' => [
                'total' => \App\Models\ingaz\Order::where('user_id', $user->id)->count(),
                'active' => \App\Models\ingaz\Order::where('user_id', $user->id)
                    ->whereNotIn('status', ['done', 'refused', 'cancelled'])->count(),
                'completed' => \App\Models\ingaz\Order::where('user_id', $user->id)
                    ->where('status', 'done')->count(),
            ],
            'contracts' => [
                'total' => \App\Models\Contract::where('user_id', $user->id)->count(),
                'active' => \App\Models\Contract::where('user_id', $user->id)
                    ->whereNotIn('status', ['arrival', 'closed', 'shelter'])->count(),
                'completed' => \App\Models\Contract::where('user_id', $user->id)
                    ->whereIn('status', ['arrival', 'closed'])->count(),
            ],
            'complaints' => [
                'total' => \App\Models\ingaz\Complaint::where('user_id', $user->id)->count(),
                'pending' => \App\Models\ingaz\Complaint::where('user_id', $user->id)
                    ->where('status', 'pending')->count(),
                'resolved' => \App\Models\ingaz\Complaint::where('user_id', $user->id)
                    ->where('status', 'closed')->count(),
            ]
        ];

        // Get recent orders
        $recentOrders = \App\Models\ingaz\Order::where('user_id', $user->id)
            ->with(['maid.nationality', 'jobType', 'customer:id,name,phone,whatsapp'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent contracts
        $recentContracts = \App\Models\Contract::where('user_id', $user->id)
            ->with(['maid'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get recent complaints
        $recentComplaints = \App\Models\ingaz\Complaint::where('user_id', $user->id)
            ->with(['complaintType'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get sponsorship transfers for the user
        $sponsorshipTransfers = \App\Models\SponsorshipTransfer::with([
            'contract',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy'
        ])->where(function ($query) use ($user) {
            $query->where('old_sponsor_id', $user->id)
                ->orWhere('new_sponsor_id', $user->id);
        })->orderBy('created_at', 'desc')->get();

        $notifications_count = \App\Models\ingaz\Notification::where('user_id', $user->id)->count();

        $data = [
            "stats" => $stats,
            "recentOrders" => $recentOrders,
            "recentContracts" => $recentContracts,
            "recentComplaints" => $recentComplaints,
            "sponsorshipTransfers" => $sponsorshipTransfers,
            "notifications_count" => $notifications_count,
        ];

        return apiResponse(true, __('api.All items'), $data, 200);
    }

    public function update_profile(Request $request, UserAction $action)
    {
        logger()->info('Update profile request data: ', $request->all());

        try {
            $dataInsert = $request->only(['name', 'email']);

            // Validate the data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . auth()->id(),
            ], [
                'name.required' => 'الاسم مطلوب.',
                'name.string'   => 'الاسم يجب أن يكون نصًا.',
                'name.max'      => 'الاسم يجب ألا يتجاوز 255 حرفًا.',
                'email.email'   => 'يرجى إدخال بريد إلكتروني صحيح.',
                'email.unique'  => 'هذا البريد الإلكتروني مستخدم بالفعل.',
            ]);

            $data = $action->updateUser(auth()->id(), $dataInsert);

            if ($data) {
                return apiResponse(true, helperTrans('api.data_updated_successfully'), [], 200);
            } else {
                return apiResponse(false, helperTrans('api.error_updating_profile'), [], 400);
            }
        } catch (\Exception $e) {
            return apiResponse(false, helperTrans('api.error_updating_profile'), [], 500);
        }
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
