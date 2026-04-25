<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    // public function index()
    // {
    //     $orders = RecruitmentRequest::where('user_id', auth()->user()->id)->whereHas('maid', function ($query) {
    //         $query->whereHas('jobTypes', function ($q) {
    //             $q->where('job_type_id', 1);
    //         });
    //     })->get();
    //     return view('site.pages.profile.index_new', compact('orders'));
    // }

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
            ->with(['maid', 'jobType'])
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
        ])->where(function($query) use ($user) {
            $query->where('old_sponsor_id', $user->id)
                  ->orWhere('new_sponsor_id', $user->id);
        })->orderBy('created_at', 'desc')->get();

        return view('site.pages.profile.index_new', compact('stats', 'recentOrders', 'recentContracts', 'recentComplaints', 'sponsorshipTransfers'));
    }

    public function edit($id, Request $request)
    {
        $user = auth()->user();
        return view('site.pages.profile.edit_profile', compact('user'));
    }

    public function update($id, Request $request, UserAction $action)
    {
        try {
            $dataInsert = $request->only(['name', 'email']);

            // Validate the data
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . auth()->id(),
            ]);

            $data = $action->updateUser(auth()->id(), $dataInsert);

            if ($data) {
                return response()->json([
                    'success' => true,
                    'message' => helperTrans('web.data_updated_successfully')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => helperTrans('web.error_updating_profile')
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => helperTrans('web.error_updating_profile')
            ], 500);
        }
    }
}
