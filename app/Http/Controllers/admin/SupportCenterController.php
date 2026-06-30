<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\SupportArticleAttachment;
use App\Models\SupportKnowledgeArticle;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupportCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportKnowledgeArticle::query()
            ->with('attachments')
            ->withCount('attachments');

        if ($request->filled('q')) {
            $search = $request->input('q', '');
            $query->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category', ''));
        }

        $articles = $query->orderBy('sort_order')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $categories = SupportKnowledgeArticle::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $categoryCounts = SupportKnowledgeArticle::query()
            ->selectRaw('COALESCE(category, ?) as category_name, COUNT(*) as total', ['عام'])
            ->groupBy('category_name')
            ->orderBy('category_name')
            ->pluck('total', 'category_name');

        $totalArticles = SupportKnowledgeArticle::count();
        $publishedArticles = SupportKnowledgeArticle::where('is_published', true)->count();
        $mediaArticles = SupportKnowledgeArticle::has('attachments')->count();

        return view('admin.support.index', compact(
            'articles',
            'categories',
            'categoryCounts',
            'totalArticles',
            'publishedArticles',
            'mediaArticles'
        ));
    }

    public function show(SupportKnowledgeArticle $article)
    {
        $article->load('attachments');

        return view('admin.support.show', compact('article'));
    }

    public function create()
    {
        return view('admin.support.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateArticle($request);
        $data['created_by'] = auth('admin')->id();
        $data['updated_by'] = auth('admin')->id();
        $data['is_published'] = $request->boolean('is_published', true);

        $article = SupportKnowledgeArticle::create($data);
        $this->storeAttachments($request, $article);

        return redirect()->route('support.index')->with('success', 'تم إضافة الدليل بنجاح');
    }

    public function edit(SupportKnowledgeArticle $article)
    {
        $article->load('attachments');

        return view('admin.support.edit', compact('article'));
    }

    public function update(Request $request, SupportKnowledgeArticle $article)
    {
        $data = $this->validateArticle($request);
        $data['updated_by'] = auth('admin')->id();
        $data['is_published'] = $request->boolean('is_published');

        $article->update($data);
        $this->storeAttachments($request, $article);

        return redirect()->route('support.show', $article)->with('success', 'تم تحديث الدليل بنجاح');
    }

    public function destroy(SupportKnowledgeArticle $article)
    {
        foreach ($article->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $article->delete();

        return redirect()->route('support.index')->with('success', 'تم حذف الدليل بنجاح');
    }

    public function destroyAttachment(SupportArticleAttachment $attachment)
    {
        $article = $attachment->article;
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return redirect()->route('support.edit', $article)->with('success', 'تم حذف المرفق');
    }

    public function tickets(Request $request)
    {
        $query = SupportTicket::query()
            ->with(['creator', 'assignee'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status', ''));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority', ''));
        }

        if ($request->filled('q')) {
            $search = $request->input('q', '');
            $query->where(function ($inner) use ($search) {
                $inner->where('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('admin.support.tickets', compact('tickets'));
    }

    public function createTicket()
    {
        return view('admin.support.create-ticket');
    }

    public function storeTicket(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $data['created_by'] = auth('admin')->id();

        SupportTicket::create($data);

        return redirect()->route('support.tickets')->with('success', 'تم فتح تذكرة الدعم بنجاح');
    }

    private function validateArticle(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'attachments.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,pdf,doc,docx', 'max:51200'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function storeAttachments(Request $request, SupportKnowledgeArticle $article): void
    {
        if (!$request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store('support-center', 'public');

            $article->attachments()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}
