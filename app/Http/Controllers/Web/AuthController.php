<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserType;
use App\Http\Actions\ingaz\PhoneVerificationAction as objAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\auth\ConfirmCodeRequest;
use App\Http\Requests\ingaz\auth\SendCodeRequest;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * @var string[]  fillable data
     */

    public $postData = ['phone_code','phone'];

    public function index()
    {
        return view('web.ingaz.auth.index');
    }

    public function resetPassword()
    {
        return view('web.ingaz.auth.reset_password');
    }

    public function sendOtp(SendCodeRequest $request, objAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSendCode($dataInsert,$request);
        Session::put('code', $data['code']);

        return redirect('otp?phone='.request()->phone)->with($data);
    }

    public function otp(objAction $action)
    {
        $user = User::where('phone', request()->phone)->first();
        $data['user'] = $action->getWhere(['phone'=>request()->phone])->first();
        $data['is_existing_user'] = $user ? true : false;
        return view('web.ingaz.auth.otp', $data);
    }

    public function confirm_code(ConfirmCodeRequest $request, objAction $action)
    {
        $response = $action->confirmCode($request);

        if (!$response) {
            return response()->json([
                'code' => 422,
                'message' => __('api.The code is wrong')
            ], 422);
        }

        // Log the request data
        \Log::info('Confirm Code Request:', [
            'phone' => $request->phone,
            'phone_code' => $request->phone_code,
            'has_first_name' => $request->has('first_name'),
            'has_last_name' => $request->has('last_name')
        ]);

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        // Log the user check result
        \Log::info('User Check:', [
            'user_found' => $user ? true : false,
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : null
        ]);

        if ($user) {
            // Existing user - login directly
            Auth::guard('web')->login($user);
            return response()->json([
                'code' => 200,
                'message' => __('api.Code verified successfully')
            ]);
        }

        // New user - check if name is provided
        if (!$request->has('first_name') || !$request->has('last_name')) {
            return response()->json([
                'code' => 422,
                'message' => __('api.Please enter your name'),
                'is_new_user' => true
            ], 422);
        }

        // Create new user with first and last name
        $user = User::create([
            'phone' => $request->phone,
            'phone_code' => $request->phone_code,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . ' ' . $request->last_name,
            'status' => 1
        ]);

        Auth::guard('web')->login($user);
        return response()->json([
            'code' => 200,
            'message' => __('api.Code verified successfully')
        ]);
    }

    public function dashboard()
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

        return view('web.ingaz.auth.dashboard', compact('stats', 'recentOrders', 'recentContracts', 'recentComplaints', 'sponsorshipTransfers'));
    }

    public function profile()
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

        return view('web.ingaz.auth.dashboard', compact('stats', 'recentOrders', 'recentContracts', 'recentComplaints', 'sponsorshipTransfers'));
    }

    public function updateProfile(Request $request, UserAction $action)
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

    public function editProfile()
    {
        $user = auth()->user();
        return view('web.ingaz.auth.edit-profile', compact('user'));
    }

    public function logout()
    {
        Auth::guard('web')->logout();
        return redirect('/');
    }
}
