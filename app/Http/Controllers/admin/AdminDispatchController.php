<?php

namespace App\Http\Controllers\admin;

use App\Jobs\DeliverAdminDispatch;
use App\Models\AdminNotification;
use App\Models\auth\Admin;
use App\Models\Role;
use App\Support\AdminDispatchPermissions as Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminDispatchController extends Controller
{
    public function index(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless(Permissions::allows($admin, Permissions::VIEW), 403);
        $allowed = collect(Permissions::definitions())->keys()->filter(fn ($key) => Permissions::allows($admin, $key))->all();

        return view('admin.notifications.dispatch', [
            'allowed' => $allowed,
            'admins' => in_array(Permissions::AUDIENCES['users'], $allowed, true)
                ? Admin::activeForOperations()->notExternalOfficeAgents()->orderBy('name')->get(['id', 'name']) : collect(),
            'roles' => in_array(Permissions::AUDIENCES['roles'], $allowed, true)
                ? Role::where('guard_name', 'admin')->orderBy('name')->get(['id', 'name']) : collect(),
            'history' => AdminNotification::with('admin:id,name')->where('created_by', $admin->id)
                ->where('type', 'admin_dispatch')->latest()->paginate(25),
        ]);
    }

    public function store(Request $request)
    {
        $admin = $request->user('admin');
        abort_unless(Permissions::allows($admin, Permissions::VIEW) && Permissions::allows($admin, Permissions::SEND), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'audience' => ['required', Rule::in(array_keys(Permissions::AUDIENCES))],
            'users' => ['required_if:audience,users', 'nullable', 'array', 'max:500'],
            'users.*' => ['integer', 'distinct', Rule::exists('admins', 'id')->whereNull('deleted_at')],
            'roles' => ['required_if:audience,roles', 'nullable', 'array', 'max:100'],
            'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
            'channels' => ['nullable', 'array', 'max:3'],
            'channels.*' => ['required', 'distinct', Rule::in(array_keys(Permissions::CHANNELS))],
        ]);
        abort_unless(Permissions::allows($admin, Permissions::AUDIENCES[$data['audience']]), 403);
        foreach ($data['channels'] ?? [] as $channel) {
            abort_unless(Permissions::allows($admin, Permissions::CHANNELS[$channel]), 403);
        }

        $recipients = Admin::activeForOperations()->notExternalOfficeAgents();
        if ($data['audience'] === 'users') {
            $recipients->whereKey($data['users']);
        } elseif ($data['audience'] === 'roles') {
            $recipients->where(fn ($query) => $query->whereIn('role_id', $data['roles'])
                ->orWhereHas('roles', fn ($roles) => $roles->whereIn('roles.id', $data['roles'])->where('guard_name', 'admin')));
        }
        $ids = $recipients->pluck('id');
        if ($ids->isEmpty()) {
            return back()->withErrors(['audience' => 'لا يوجد أدمنز نشطون ضمن المستلمين المختارين.'])->withInput();
        }

        DB::transaction(function () use ($ids, $admin, $data) {
            foreach ($ids as $id) {
                $notification = AdminNotification::create([
                    'admin_id' => $id, 'created_by' => $admin->id,
                    'title' => $data['title'], 'message' => $data['message'], 'type' => 'admin_dispatch',
                    'data' => ['audience' => $data['audience'], 'delivery' => array_fill_keys($data['channels'] ?? [], 'pending')],
                ]);
                if (!empty($data['channels'])) {
                    DeliverAdminDispatch::dispatch($notification->id)->afterCommit();
                }
            }
        });

        return redirect()->route('admin.dispatch.index')->with('success', 'تم حفظ الإشعارات لـ '.$ids->count().' مستلم وإضافة القنوات المختارة للإرسال. تابع حالة الإرسال في السجل.');
    }
}
