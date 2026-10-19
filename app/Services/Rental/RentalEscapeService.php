<?php

namespace App\Services\Rental;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\RentalEscapeReport;
use App\Models\ingaz\Maid;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentalEscapeService
{
    public function record(RentalContract $contract, array $data, ?array $attachment): RentalEscapeReport
    {
        $maid = Maid::query()->lockForUpdate()->findOrFail($contract->maid_id);
        $this->assertNoActiveRental($maid->id);
        if (RentalEscapeReport::withoutGlobalScopes()->where('maid_id', $maid->id)->whereNull('ready_at')->exists()) {
            throw ValidationException::withMessages(['closure_reason' => 'يوجد بلاغ هروب غير مغلق أو عودة بانتظار المراجعة لهذه العاملة.']);
        }
        $report = RentalEscapeReport::create([
            'rental_contract_id' => $contract->id, 'maid_id' => $maid->id, 'branch_id' => $contract->branch_id,
            'escape_date' => $data['escape_date'], 'reference_number' => $data['escape_reference'] ?? null,
            'notes' => $data['escape_notes'], 'attachment' => $attachment, 'reported_by' => auth('admin')->id(),
        ]);
        $maid->update(['status' => 'escaped', 'available' => false, 'is_show' => false]);
        // Preserve physical absence; reconciliation must not treat the closed rental as a return.
        foreach ($this->shelterContracts($maid->id) as $shelter) {
            $shelter->info()->updateOrCreate([], [
                'is_outside_shelter' => true, 'outside_shelter_reason' => 'escape_report',
                'outside_shelter_at' => $data['escape_date'], 'outside_shelter_by' => auth('admin')->id(),
                'outside_shelter_reference_type' => RentalEscapeReport::class,
                'outside_shelter_reference_id' => $report->id, 'is_available_for_instant' => false,
                'escape_report_note' => $data['escape_notes'], 'escape_report_attachment' => $attachment,
            ]);
        }
        return $report;
    }

    public function registerReturn(RentalContract $contract, array $data): void
    {
        DB::transaction(function () use ($contract, $data) {
            $maid = Maid::query()->lockForUpdate()->findOrFail($contract->maid_id);
            $report = RentalEscapeReport::where('rental_contract_id', $contract->id)->lockForUpdate()->firstOrFail();
            if ($report->closed_without_return_at) {
                throw ValidationException::withMessages(['return_date' => 'أُغلق البلاغ دون عودة؛ لا يمكن تسجيل العودة على بلاغ مغلق.']);
            }
            if ($report->returned_at || Carbon::parse($data['return_date'])->lt($report->escape_date)) {
                throw ValidationException::withMessages(['return_date' => 'تم تسجيل العودة مسبقًا أو أن تاريخ العودة يسبق تاريخ الهروب.']);
            }
            $this->assertNoActiveRental($maid->id);
            $report->update(['returned_at' => $data['return_date'], 'return_notes' => $data['return_notes'], 'returned_by' => auth('admin')->id()]);
            $maid->update(['status' => 'returned_pending_review', 'available' => false, 'is_show' => false]);
            foreach ($this->shelterContracts($maid->id) as $shelter) {
                if ($shelter->info?->outside_shelter_reference_type === RentalEscapeReport::class
                    && (int) $shelter->info?->outside_shelter_reference_id === (int) $report->id) {
                    $shelter->info->update(['is_outside_shelter' => false, 'outside_shelter_reason' => null,
                        'outside_shelter_reference_type' => null, 'outside_shelter_reference_id' => null,
                        'outside_shelter_at' => null, 'outside_shelter_by' => null, 'wants_to_work' => 'unknown']);
                }
            }
        });
    }

    public function approveReadiness(RentalContract $contract, string $notes): void
    {
        DB::transaction(function () use ($contract, $notes) {
            $maid = Maid::query()->lockForUpdate()->findOrFail($contract->maid_id);
            $report = RentalEscapeReport::where('rental_contract_id', $contract->id)->lockForUpdate()->firstOrFail();
            if ($report->closed_without_return_at || !$report->returned_at || $report->ready_at || $maid->status !== 'returned_pending_review') {
                throw ValidationException::withMessages(['review_notes' => 'يلزم تسجيل عودة العاملة وانتظار المراجعة قبل اعتماد جاهزيتها.']);
            }
            $this->assertNoActiveRental($maid->id);
            if (RentalMaidQuery::isDeported($maid->id)) {
                throw ValidationException::withMessages(['review_notes' => 'العاملة مسجلة كمرحّلة؛ لا يمكن اعتماد جاهزيتها.']);
            }
            if (\App\Models\ingaz\Order::withoutGlobalScopes()->where('maid_id', $maid->id)
                ->whereIn('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])->exists()
                || Contract::withoutGlobalScopes()->where('maid_id', $maid->id)
                    ->whereIn('status', ['new', 'accreditation_contracts', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival', 'sari', 'pending_maid_selection', 'trial', 'in_progress', 'client_contracted', 'delegate_selected', 'under_warranty'])->exists()) {
                throw ValidationException::withMessages(['review_notes' => 'العاملة مرتبطة بطلب أو عقد آخر؛ راجع ارتباطاتها قبل إتاحتها.']);
            }
            $report->update(['ready_at' => now(), 'review_notes' => $notes, 'reviewed_by' => auth('admin')->id()]);
            $maid->update(['status' => 'available', 'available' => true, 'available_at' => now()->toDateString()]);
            // Register actual shelter presence only after the explicit return and review.
            app(\App\Services\RentalShelterPresenceService::class)->returnInside($contract);
        });
    }

    public function closeWithoutReturn(RentalContract $contract, array $data, array $attachment): void
    {
        DB::transaction(function () use ($contract, $data, $attachment) {
            $maid = Maid::query()->lockForUpdate()->findOrFail($contract->maid_id);
            $report = RentalEscapeReport::where('rental_contract_id', $contract->id)->lockForUpdate()->firstOrFail();
            if ($report->closed_without_return_at || $report->returned_at || $report->ready_at) {
                throw ValidationException::withMessages(['no_return_reason' => 'يُسمح بالإغلاق دون عودة للبلاغ المفتوح فقط، قبل تسجيل عودة العاملة.']);
            }
            if (Carbon::parse($data['no_return_date'])->lt($report->escape_date)) {
                throw ValidationException::withMessages(['no_return_date' => 'لا يمكن أن يسبق تاريخ الإغلاق تاريخ الهروب.']);
            }
            $this->assertNoActiveRental($maid->id);
            $report->update([
                'closed_without_return_date' => $data['no_return_date'], 'closed_without_return_at' => now(),
                'closed_without_return_by' => auth('admin')->id(), 'no_return_reason' => $data['no_return_reason'],
                'no_return_attachment' => $attachment,
            ]);
            // Keep ready_at null: the availability guard also protects reports closed without return.
            $maid->update(['available' => false, 'is_show' => false]);
        });
    }

    private function assertNoActiveRental(int $maidId): void
    {
        if (RentalContract::withoutGlobalScopes()->where('maid_id', $maidId)->whereIn('status', RentalMaidQuery::ACTIVE_RENTAL_STATUSES)->holdingMaid()->exists()) {
            throw ValidationException::withMessages(['maid_id' => 'العاملة مرتبطة بعقد تأجير نشط؛ راجع العقد قبل تغيير حالتها.']);
        }
    }

    private function shelterContracts(int $maidId)
    {
        return Contract::query()->where('maid_id', $maidId)->where('status', ContractStatusEnum::Shelter->value)->with('info')->lockForUpdate()->get();
    }
}
