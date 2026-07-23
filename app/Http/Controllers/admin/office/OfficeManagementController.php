<?php

namespace App\Http\Controllers\admin\office;

use App\Http\Controllers\admin\Controller;
use App\Models\auth\Admin;
use App\Models\office\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OfficeManagementController extends Controller
{
    public function users(Request $request)
    {
        $query = Admin::query()
            ->with(['role', 'agentOffices.nationality'])
            ->whereHas('agentOfficeLinks')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('office_id')) {
            $officeId = (int) $request->input('office_id');
            $query->whereHas('agentOfficeLinks', function ($subQuery) use ($officeId) {
                $subQuery->where('office_id', $officeId);
            });
        }

        $users = $query->paginate(25)->withQueryString();
        $offices = Office::query()
            ->get()
            ->sortBy(fn ($office) => mb_strtolower((string) ($office->title ?? '')))
            ->values();

        return view('admin.office.agent_users.index', compact('users', 'offices'));
    }

    public function editUser(Request $request, Admin $user)
    {
        abort_unless($user->agentOfficeLinks()->exists(), 404);

        if (! $request->ajax()) {
            return redirect()->route('offices.users.index');
        }

        return jsonSuccess([
            'html' => view('admin.office.agent_users.edit', compact('user'))->render(),
        ]);
    }

    public function updateUser(Request $request, Admin $user)
    {
        abort_unless($user->agentOfficeLinks()->exists(), 404);

        $linkedOfficeIds = $user->agentOffices()->pluck('recruitment_offices.id');

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9._@-]+$/',
                Rule::unique('admins', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($user->id),
                function ($attribute, $value, $fail) use ($linkedOfficeIds) {
                    $usedByAnotherOffice = DB::table('recruitment_offices')
                        ->where('manager_email', $value)
                        ->when(
                            $linkedOfficeIds->isNotEmpty(),
                            fn ($query) => $query->whereNotIn('id', $linkedOfficeIds)
                        )
                        ->exists();

                    if ($usedByAnotherOffice) {
                        $fail(__('ingaz.manager_email_unique'));
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'username.required' => 'اسم المستخدم مطلوب.',
            'username.regex' => __('validation.username_format'),
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
        ]);

        DB::transaction(function () use ($user, $validated, $linkedOfficeIds) {
            $userData = [
                'username' => $validated['username'],
                'email' => $validated['email'],
            ];

            if (filled($validated['password'] ?? null)) {
                $userData['password'] = Hash::make($validated['password']);
            }

            $user->update($userData);

            DB::table('recruitment_offices')
                ->whereIn('id', $linkedOfficeIds)
                ->update([
                    'manager_email' => $validated['email'],
                    'updated_at' => now(),
                ]);
        });

        return jsonSuccess(
            ['url' => route('offices.users.index')],
            'تم تحديث بيانات دخول مستخدم الوكيل بنجاح.',
            201
        );
    }

    public function activateUser(Admin $user)
    {
        abort_unless($user->agentOfficeLinks()->exists(), 404);

        if ($user->status === 'suspended') {
            $user->update(['status' => 'active']);
        }

        return redirect()
            ->route('offices.users.index', request()->only(['search', 'office_id', 'page']))
            ->with('success', 'تم تفعيل حساب مستخدم الوكيل بنجاح.');
    }

    public function files(Request $request)
    {
        $query = Office::query()
            ->with(['nationality', 'file.jobTitle'])
            ->withCount('maids')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->whereTranslationLike('title', "%{$search}%")
                    ->orWhere('manager_name', 'like', "%{$search}%")
                    ->orWhere('manager_email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('file_status')) {
            $request->input('file_status') === 'exists'
                ? $query->whereHas('file')
                : $query->whereDoesntHave('file');
        }

        $offices = $query->paginate(25)->withQueryString();

        return view('admin.office.files.index', compact('offices'));
    }
}
