<?php

namespace App\Http\Controllers\Admin;

use App\Models\ingaz\Complaint;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::latest()->get();
        $unreadCount = Complaint::where('is_read', false)->count();

        return view('admin.complaints.index', compact('complaints', 'unreadCount'));
    }

    public function markAsRead($id)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Complaint::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
