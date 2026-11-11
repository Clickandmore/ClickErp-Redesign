<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Jobs\SendMaidCvShare;
use App\Models\MaidCvShare;
use App\Models\MaidCvShareMessage;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Services\BranchAccessService;
use App\Services\BranchDataSharingService;
use App\Services\ingaz\MaidCvShareService;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\SystemModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MaidCvShareController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:maids_view|maids_rental_view,admin')->except('file');
        $this->middleware('permission:maids_share_whatsapp,admin')->except('file');
    }

    private function clientsQuery()
    {
        $query = User::query();
        if (SystemModuleManager::isEnabled('branches') && Schema::hasColumn('users', 'branch_id')
            && !@AdminGuardHasPerm(\App\PermKeys::BRANCHES_VIEW_ALL)
            && !@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW_ALL_BRANCHES)) {
            $branchId = auth('admin')->user()?->branch_id;
            if (!$branchId) return $query->whereRaw('1 = 0');
            app(BranchAccessService::class)->applyBranchIdToQuery($query, (int) $branchId, 'users', null,
                app(BranchDataSharingService::class)->sharedSourceBranchIds(BranchDataSharingService::CLIENTS));
        }
        return $query;
    }

    private function accessibleShare(int $id): MaidCvShare
    {
        return $this->sharesQuery()->findOrFail($id);
    }

    private function sharesQuery()
    {
        $query = MaidCvShare::whereIn('client_id', $this->clientsQuery()->select('users.id'));
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW)) {
            $query->whereDoesntHave('messages', fn ($q) => $q->whereNotNull('maid_id')->where(fn ($q) =>
                $q->whereNull('snapshot->cv_type')->orWhere('snapshot->cv_type', '!=', 'rental')));
        }
        if (!@AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)) {
            $query->whereDoesntHave('messages', fn ($q) => $q->where('snapshot->cv_type', 'rental'));
        }
        return $query;
    }

    private function authorizeMaids($maids): void
    {
        if (@AdminGuardHasPerm(\App\PermKeys::MAIDS_VIEW) && @AdminGuardHasPerm(\App\PermKeys::MAIDS_RENTAL_VIEW)) return;
        foreach ($maids as $maid) {
            abort_unless(@AdminGuardHasPerm($maid->isRentalCv() ? \App\PermKeys::MAIDS_RENTAL_VIEW : \App\PermKeys::MAIDS_VIEW), 403);
        }
    }

    public function create(Request $request, WhatsAppMessagingService $whatsapp)
    {
        $validated = $request->validate(['maid_ids' => ['required', 'array', 'min:1', 'max:20'],
            'maid_ids.*' => ['required', 'integer', 'distinct']]);
        $maids = Maid::query()->approvedSubmission()->with(['nationality', 'jobTypes', 'jobTitles', 'religion'])->whereIn('id', $validated['maid_ids'])->get();
        if ($maids->count() !== count($validated['maid_ids'])) {
            throw ValidationException::withMessages(['maid_ids' => 'بعض السير غير متاحة أو ليس لديك صلاحية الوصول إليها.']);
        }
        $this->authorizeMaids($maids);
        if ($request->wantsJson()) {
            return response()->json(['html' => view('admin.ingaz.maid-cv-shares.partials.form', compact('maids', 'whatsapp'))->render()]);
        }
        return view('admin.ingaz.maid-cv-shares.create', compact('maids', 'whatsapp'));
    }

    public function clients(Request $request)
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $clients = $this->clientsQuery()->where('is_blocked', false)->where(function ($q) use ($validated) {
            $q->where('name', 'like', '%' . $validated['q'] . '%')->orWhere('phone', 'like', '%' . $validated['q'] . '%');
        })->orderBy('name')->limit(25)->get();
        return response()->json($clients->map(fn ($client) => ['id' => $client->id, 'name' => $client->name,
            'phones' => array_values(array_unique(array_filter([$client->primaryPhoneValue(), $client->secondaryPhoneValue()])))]));
    }

    public function store(Request $request, MaidCvShareService $service, MetaWhatsAppService $meta)
    {
        $data = $request->validate(['maid_ids' => ['required', 'array', 'min:1', 'max:20'],
            'maid_ids.*' => ['required', 'integer', 'distinct'], 'client_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'max:30'], 'sender_key' => ['nullable', 'string', 'max:100']]);
        $client = $this->clientsQuery()->where('is_blocked', false)->findOrFail($data['client_id']);
        $allowedPhones = collect([$client->primaryPhoneValue(), $client->secondaryPhoneValue()])->filter()
            ->map(fn ($phone) => $meta->normalizePhone($phone))->filter();
        if (!$allowedPhones->contains($meta->normalizePhone($data['phone']))) {
            throw ValidationException::withMessages(['phone' => 'اختر أحد أرقام العميل المسجلة.']);
        }
        $maids = Maid::query()->approvedSubmission()->with(['nationality', 'jobTypes', 'jobTitles', 'religion'])->whereIn('id', $data['maid_ids'])->get();
        if ($maids->count() !== count($data['maid_ids'])) {
            throw ValidationException::withMessages(['maid_ids' => 'بعض السير غير متاحة أو ليس لديك صلاحية الوصول إليها.']);
        }
        $this->authorizeMaids($maids);
        $share = $service->prepare(auth('admin')->user(), $client, $maids, $data['phone'], $data['sender_key'] ?? null);
        if ($request->wantsJson()) {
            return response()->json(['redirect' => route('maid-cv-shares.show', $share->id)]);
        }
        return redirect()->route('maid-cv-shares.show', $share->id);
    }

    public function index(Request $request)
    {
        $query = $this->sharesQuery()->whereNotNull('confirmed_at');
        if ($request->filled('client_id')) $query->where('client_id', $request->integer('client_id'));
        if ($request->filled('maid_id')) {
            Maid::findOrFail($request->integer('maid_id'));
            $query->whereHas('messages', fn ($q) => $q->where('maid_id', $request->integer('maid_id')));
        }
        $shares = $query->latest()->paginate(30)->withQueryString();
        return view('admin.ingaz.maid-cv-shares.index', compact('shares'));
    }

    public function show(int $share)
    {
        $share = $this->accessibleShare($share)->load('messages');
        $service = app(MaidCvShareService::class);
        $options = (array) ($share->messages->firstWhere('maid_id', null)?->snapshot ?? []) + $service->defaultOptions();
        $editable = $share->status === 'draft' && (int) $share->admin_id === (int) auth('admin')->id()
            && $share->created_at->gte(now()->subDay());
        return view('admin.ingaz.maid-cv-shares.show', compact('share', 'options', 'editable'));
    }

    /** حفظ تعديلات المراجعة على المسودة، ثم الإرسال إن طلبه الموظف. */
    public function update(Request $request, int $share, MaidCvShareService $service)
    {
        $model = $this->accessibleShare($share);
        abort_unless((int) $model->admin_id === (int) auth('admin')->id(), 403);
        abort_if($model->created_at->lt(now()->subDay()), 422, 'انتهت صلاحية المعاينة؛ جهز مشاركة جديدة.');
        $data = $request->validate([
            'fields' => ['nullable', 'array'], 'fields.*' => ['string', 'in:' . implode(',', array_keys(MaidCvShareService::FIELDS))],
            'intro' => ['nullable', 'string', 'max:1000'], 'outro' => ['nullable', 'string', 'max:1000'],
            'custom' => ['nullable', 'boolean'], 'message' => ['nullable', 'string', 'max:' . MaidCvShareService::MAX_LENGTH],
            'items' => ['nullable', 'array'], 'items.*.include' => ['nullable', 'boolean'],
            'items.*.name' => ['nullable', 'string', 'max:150'], 'items.*.reference' => ['nullable', 'string', 'max:50'],
            'items.*.nationality' => ['nullable', 'string', 'max:100'], 'items.*.job' => ['nullable', 'string', 'max:150'],
            'items.*.religion' => ['nullable', 'string', 'max:50'], 'items.*.age' => ['nullable', 'string', 'max:20'],
            'items.*.experience' => ['nullable', 'string', 'max:100'], 'items.*.salary' => ['nullable', 'string', 'max:50'],
            'items.*.note' => ['nullable', 'string', 'max:500'], 'send' => ['nullable', 'boolean'],
        ]);
        $service->updateDraft($model, $data);
        if ($request->boolean('send')) return $this->confirm($model->id);
        return redirect()->route('maid-cv-shares.show', $model->id)->with('success', 'تم حفظ التعديلات.');
    }

    public function confirm(int $share)
    {
        $share = $this->accessibleShare($share);
        abort_unless((int) $share->admin_id === (int) auth('admin')->id(), 403);
        abort_unless($this->clientsQuery()->where('is_blocked', false)->whereKey($share->client_id)->exists(), 422, 'العميل محظور.');
        abort_if($share->created_at->lt(now()->subDay()), 422, 'انتهت صلاحية المعاينة؛ جهز مشاركة جديدة.');
        $updated = DB::transaction(function () use ($share) {
            // Check every snapshot before making the job visible to the queue.
            foreach ($share->messages()->whereNotNull('file_path')->get() as $message) {
                abort_unless(Storage::disk('local')->exists($message->file_path), 422, 'أحد ملفات السيرة غير متاح.');
                abort_unless(Maid::query()->approvedSubmission()->whereKey($message->maid_id)->exists(), 403);
                $this->authorizeMaids(Maid::query()->whereKey($message->maid_id)->setEagerLoads([])->get());
            }
            return MaidCvShare::whereKey($share->id)->where('status', 'draft')->update(['status' => 'queued', 'confirmed_at' => now()]);
        });
        if ($updated) $this->dispatchShare($share);
        return redirect()->route('maid-cv-shares.show', $share->id);
    }

    public function retry(int $share)
    {
        $share = $this->accessibleShare($share);
        abort_unless((int) $share->admin_id === (int) auth('admin')->id(), 403);
        abort_unless($this->clientsQuery()->where('is_blocked', false)->whereKey($share->client_id)->exists(), 422, 'العميل محظور.');
        $claimed = DB::transaction(function () use ($share) {
            $locked = MaidCvShare::lockForUpdate()->findOrFail($share->id);
            if (!in_array($locked->status, ['failed', 'partial'], true)) return false;
            foreach ($locked->messages()->whereNotNull('maid_id')->get() as $item) {
                abort_unless(Maid::query()->approvedSubmission()->whereKey($item->maid_id)->exists(), 403);
                $this->authorizeMaids(Maid::query()->whereKey($item->maid_id)->setEagerLoads([])->get());
            }
            if (!$locked->messages()->where('status', 'failed')->update(['status' => 'pending', 'error' => null])) return false;
            $locked->update(['status' => 'queued']);
            return true;
        });
        if ($claimed) $this->dispatchShare($share);
        return redirect()->route('maid-cv-shares.show', $share->id);
    }

    private function dispatchShare(MaidCvShare $share): void
    {
        try {
            SendMaidCvShare::dispatch($share->id);
        } catch (\Throwable $e) {
            (new SendMaidCvShare($share->id))->failed($e);
            report($e);
        }
    }

    public function download(int $message)
    {
        $message = MaidCvShareMessage::findOrFail($message);
        $this->accessibleShare($message->share_id);
        abort_unless($message->file_path, 404);
        if (request()->boolean('inline')) {
            // معاينة داخل الصفحة قبل الإرسال.
            return Storage::disk('local')->response($message->file_path, $message->filename,
                ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
        }
        return Storage::disk('local')->download($message->file_path, $message->filename, ['Content-Type' => 'application/pdf']);
    }

    public function file(int $message)
    {
        // Public access is restricted to a short-lived signed provider download URL.
        $message = MaidCvShareMessage::whereNotNull('file_path')->findOrFail($message);
        abort_unless($message->share->confirmed_at, 404);
        return Storage::disk('local')->download($message->file_path, $message->filename, ['Content-Type' => 'application/pdf']);
    }
}
