<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\SystemUpdate;
use App\Services\SystemUpdates\SystemUpdateInbox;
use Illuminate\Http\Request;

/**
 * صفحة «تحديثات النظام» للموظفين. متاحة لكل موظف في لوحة الإدارة عن قصد:
 * لا يوجد لها مدخل في AdminRoutePermissionMatrix، والإدارة نفسها في Super Admin.
 */
class SystemUpdatesController extends Controller
{
    public function index(Request $request, SystemUpdateInbox $inbox)
    {
        $admin = auth('admin')->user();
        $filter = in_array($request->query('filter'), ['pending', 'acknowledged'], true) ? $request->query('filter') : 'all';
        $category = array_key_exists((string) $request->query('category'), SystemUpdate::CATEGORIES) ? $request->query('category') : null;

        $query = $filter === 'pending' ? $inbox->pendingQuery($admin) : $inbox->visibleQuery();

        if ($filter === 'acknowledged') {
            $query->whereHas('reads', fn ($q) => $q->where('admin_id', $admin->getKey())->whereNotNull('acknowledged_at'));
        }

        $updates = $query
            ->when($category, fn ($q) => $q->where('category', $category))
            ->with('media')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $inbox->markSeen($admin, $updates->pluck('id'));

        return view('admin.system-updates.index', [
            'updates' => $updates,
            'reads' => $inbox->readsFor($admin, $updates->pluck('id')),
            'pendingCount' => $inbox->pendingQuery($admin)->count(),
            'filter' => $filter,
            'category' => $category,
        ]);
    }

    public function acknowledge(Request $request, SystemUpdate $systemUpdate, SystemUpdateInbox $inbox)
    {
        $admin = auth('admin')->user();
        abort_unless($inbox->visibleQuery()->whereKey($systemUpdate->getKey())->exists(), 404);

        $read = $inbox->acknowledge($admin, $systemUpdate);

        if ($request->expectsJson()) {
            return response()->json([
                'acknowledged_at' => $read->acknowledged_at->translatedFormat('j F Y - h:i a'),
                'pending_count' => $inbox->pendingQuery($admin)->count(),
            ]);
        }

        return back()->with('success', 'شكرًا، تم تسجيل اطلاعك على التحديث.');
    }

    public function acknowledgeAll(Request $request, SystemUpdateInbox $inbox)
    {
        $count = $inbox->acknowledgeAll(auth('admin')->user());

        if ($request->expectsJson()) {
            return response()->json(['acknowledged' => $count, 'pending_count' => 0]);
        }

        return back()->with('success', 'تم تأكيد الاطلاع على كل التحديثات.');
    }
}
