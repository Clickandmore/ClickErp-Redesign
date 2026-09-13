<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\SponsorshipTransfer;
use App\Models\TravelHistory;
use App\Models\ingaz\Complaint;
use Illuminate\Support\Carbon;

class ShelterWorkerHistoryService
{
    public function build(Contract $contract): array
    {
        // Keep model branch scopes: the history must only include accessible records.
        $contracts = Contract::query()->where(function ($query) use ($contract) {
            $contract->maid_id ? $query->where('maid_id', $contract->maid_id) : $query->whereKey($contract->id);
        })->with(['user', 'contract_status_logs.performer', 'returnRequests', 'deportationProcedure'])->get();
        $transfers = SponsorshipTransfer::whereIn('contract_id', $contracts->modelKeys())
            ->with(['newSponsor', 'failedBy'])->orderByDesc('id')->get();
        $complaints = Complaint::where(function ($query) use ($contracts, $contract) {
            $query->whereIn('contract_id', $contracts->modelKeys());
            if ($contract->maid_id) {
                $query->orWhereHas('order', fn ($order) => $order->where('maid_id', $contract->maid_id));
            }
        })->with('complaintType')->latest()->get();
        $events = collect();
        $add = function ($date, string $title, string $details = '', ?string $actor = null) use ($events) {
            if (!$date) return;
            try { $date = Carbon::parse($date); } catch (\Throwable $e) { return; }
            if ($date->year < 1900) return;
            $events->push(compact('date', 'title', 'details', 'actor'));
        };
        $add($contract->maid?->created_at, 'تسجيل العاملة في النظام');
        foreach ($contracts as $record) {
            $reference = 'عقد #' . $record->id;
            $add($record->created_at, 'إنشاء عقد للعاملة', $reference . ' — ' . ($record->user?->name ?: 'بدون كفيل مسجل'));
            foreach ($record->contract_status_logs as $log) {
                $label = ContractStatusEnum::tryFrom((string) $log->status)?->lang() ?: $log->status;
                $add($log->date ?: $log->created_at, $label, $reference . ($log->notes ? ' — ' . $log->notes : ''), $log->performer?->name);
            }
            foreach ($record->returnRequests as $return) {
                $add($return->created_at, 'طلب إرجاع العاملة', $reference . ' — ' . $return->status_text . ' — ' . $return->notes);
                $add($return->processed_at, 'معالجة طلب الإرجاع', $reference . ' — ' . $return->status_text . ' — ' . $return->admin_notes);
            }
            $add($record->deportationProcedure?->completed_at, 'اكتمال ترحيل العاملة', $reference);
        }
        foreach ($transfers as $transfer) {
            $reference = 'نقل خدمات #' . $transfer->id . ' — الكفيل: ' . ($transfer->newSponsor?->name ?: 'غير مسجل');
            $add($transfer->created_at, 'طلب نقل خدمات', $reference);
            $add($transfer->maid_delivered_at, 'تسليم العاملة للكفيل', $reference);
            $add($transfer->trial_started_at ?: $transfer->trial_start_date, 'بدء تجربة العاملة', $reference);
            $add($transfer->failed_at, 'فشل تجربة نقل الخدمات', $reference . ' — ' . ($transfer->failure_reason ?: 'لم يسجل سبب'), $transfer->failedBy?->name);
            $add($transfer->transferred_at, 'اكتمال نقل الخدمات', $reference);
        }
        if ($contract->maid_id) {
            foreach (RentalContract::where('maid_id', $contract->maid_id)->with('user')->get() as $rental) {
                $reference = 'عقد إيجار #' . $rental->id . ' — ' . ($rental->user?->name ?: 'بدون كفيل مسجل');
                foreach (['created_at' => 'إنشاء عقد إيجار', 'maid_delivered_at' => 'تسليم العاملة للإيجار', 'trial_started_at' => 'بدء تجربة الإيجار', 'frozen_at' => 'تجميد عقد الإيجار', 'resumed_at' => 'استئناف عقد الإيجار', 'closed_at' => 'إغلاق عقد الإيجار'] as $field => $title) {
                    $add($rental->$field, $title, $reference);
                }
            }
            foreach (TravelHistory::where('maid_id', $contract->maid_id)->get() as $travel) {
                $add($travel->created_at, 'تسجيل رحلة للعاملة', 'رحلة #' . $travel->id . ' — ' . $travel->notes);
                if ($travel->status === 'completed') $add($travel->resolveArrivalDateTime(), 'وصول العاملة', $travel->arrival_airport ?: '');
            }
        }
        foreach ($complaints as $complaint) {
            $add($complaint->complaint_date ?: $complaint->created_at, 'تسجيل شكوى #' . $complaint->id, $complaint->subject ?: $complaint->complaintType?->name ?: 'شكوى');
            $add($complaint->resolved_at, 'حل الشكوى #' . $complaint->id);
            $add($complaint->closed_at, 'إغلاق الشكوى #' . $complaint->id);
        }
        return [
            'contracts' => $contracts, 'transfers' => $transfers, 'complaints' => $complaints,
            'failed_trials' => $transfers->filter(fn ($transfer) => $transfer->status === 'failed' || $transfer->failed_at !== null)->count(),
            'open_complaints' => $complaints->whereNotIn('status', ['resolved', 'closed', 'rejected'])->count(),
            'events' => $events->sortBy('date')->values(),
        ];
    }
}
