<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\ShelterDocument;
use App\Models\SponsorshipTransfer;
use App\Models\DeportationProcedure;
use App\Support\ShelterDocumentFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ShelterDocumentMovementService
{
    public const ACTIONS = [
        'back' => ['ShelterContract.back', 'إخراج العاملة وإرجاع العقد إلى مكتمل'],
        'vacation' => ['ShelterContract.vacation', 'خروج العاملة في إجازة'],
        'rental' => ['RentContract.deliverMaid', 'تسليم العاملة لعقد الإيجار'],
        'transfer' => ['SponsorshipTransfer.deliver-maid', 'تسليم العاملة لنقل الخدمات'],
        'deportation' => ['ShelterContract.deportation.update', 'إكمال الترحيل'],
    ];

    public function available(): bool
    {
        return Schema::hasColumn('shelter_documents', 'release_completed_at');
    }

    public function inside(Contract $contract): bool
    {
        return $contract->status === ContractStatusEnum::Shelter->value && !$contract->info?->is_outside_shelter;
    }

    public function configuration(Contract $contract, string $kind): ?ShelterDocument
    {
        return ShelterDocument::where('contract_id', $contract->id)->where('kind', $kind)->where('enabled', true)->first();
    }

    public function version(Contract $contract): string
    {
        $revision = $this->configuration($contract, 'release')?->revision;
        return hash('sha256', implode('|', [
            $contract->id, $contract->status, (string) $contract->updated_at,
            (string) $contract->info?->updated_at, $revision,
        ]));
    }

    public function resolve(string $action, int $reference): ?Contract
    {
        if ($action === 'rental') {
            $rental = RentalContract::findOrFail($reference);
            if (!$rental->maid_id) return null;
            return Contract::with('info')->where('maid_id', $rental->maid_id)
                ->where('status', ContractStatusEnum::Shelter->value)
                ->whereHas('info', fn ($q) => $q->where(function ($q) {
                    $q->whereNull('is_outside_shelter')->orWhere('is_outside_shelter', false);
                }))->latest('id')->first();
        }
        if ($action === 'transfer') {
            $transfer = SponsorshipTransfer::findOrFail($reference);
            return $transfer->contract_id ? Contract::findOrFail($transfer->contract_id) : null;
        }
        if ($action === 'deportation') {
            return Contract::findOrFail(DeportationProcedure::findOrFail($reference)->contract_id);
        }
        return Contract::findOrFail($reference);
    }

    public function releaseDefaults(Contract $contract, string $action, int $reference): array
    {
        $values = app(ShelterDocumentService::class)->defaults($contract)['release'];
        $values['date'] = now()->toDateString();
        $values['exit_time'] = now()->format('H:i');
        $values['reason'] = [match ($action) {
            'rental', 'back' => 'employer', 'transfer' => 'transfer', 'deportation' => 'repatriation', default => 'other',
        }];
        if ($action === 'vacation') $values['other_reason'] = 'إجازة';
        $recipient = match ($action) {
            'rental' => RentalContract::findOrFail($reference)->user,
            'transfer' => SponsorshipTransfer::findOrFail($reference)->newSponsor,
            'back' => $contract->user,
            default => null,
        };
        if ($recipient) {
            $values['recipient_name'] = $recipient->name;
            $values['recipient_phone'] = $recipient->phone;
            $values['recipient_id'] = method_exists($recipient, 'documentNumber') ? $recipient->documentNumber() : ($recipient->national_num ?? '');
        }
        return $values;
    }

    public function releaseData(Request $request): array
    {
        $rules = [];
        foreach (ShelterDocumentFields::fields('release') as $key => $field) {
            $rules[$key] = match ($field[1]) {
                'date' => 'nullable|date_format:Y-m-d', 'time' => 'nullable|date_format:H:i',
                'choices' => 'nullable|array', 'textarea' => 'nullable|string|max:5000', default => 'nullable|string|max:255',
            };
            if ($field[1] === 'choices') $rules["$key.*"] = ['string', Rule::in(array_keys($field[2]))];
        }
        foreach (['worker_name', 'recipient_name', 'recipient_id', 'recipient_phone', 'recipient_capacity', 'employee_name'] as $key) {
            $rules[$key] = 'required|string|max:255';
        }
        $rules['date'] = 'required|date_format:Y-m-d|before_or_equal:today';
        $rules['exit_time'] = 'required|date_format:H:i';
        $rules['reason'] = 'required|array|min:1';
        $input = $request->input('shelter_release', []);
        if (!is_array($input)) $input = [];
        $data = Validator::make($input, $rules)->validate();
        return array_intersect_key($data, ShelterDocumentFields::fields('release')) + ['belongings' => []];
    }

    public function saveReleaseData(ShelterDocument $configuration, array $data): void
    {
        $configuration->update([
            'data' => $data, 'release_completed_at' => now(),
            'revision' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }

    public function refreshAdmissionData(Contract $contract): void
    {
        if (!$this->available()) return;
        $configuration = $this->configuration($contract, 'entry');
        if ($configuration) {
            $data = app(ShelterDocumentService::class)->defaults($contract->fresh())['entry'];
            $data['date'] = $data['entry_date'] = now()->toDateString();
            $configuration->update(['data' => $data]);
        }
        ShelterDocument::where('contract_id', $contract->id)->where('kind', 'release')->update([
            'release_completed_at' => null,
            'revision' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}
