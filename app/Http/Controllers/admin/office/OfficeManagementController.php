<?php

namespace App\Http\Controllers\admin\office;

use App\Http\Controllers\admin\Controller;
use App\Models\auth\Admin;
use App\Models\office\Office;
use Illuminate\Http\Request;

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
            $query->whereHas('agentOfficeLinks', function ($subQuery) use ($request) {
                $subQuery->where('office_id', $request->integer('office_id'));
            });
        }

        $users = $query->paginate(25)->withQueryString();
        $offices = Office::query()
            ->get()
            ->sortBy(fn ($office) => mb_strtolower((string) ($office->title ?? '')))
            ->values();

        return view('admin.office.agent_users.index', compact('users', 'offices'));
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
