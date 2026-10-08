<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ShelterDocument;
use App\Models\ShelterDocumentTemplate;
use App\Support\ShelterDocumentFields;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShelterDocumentService
{
    public function defaultReleaseTemplate(): ?ShelterDocumentTemplate
    {
        return ShelterDocumentTemplate::where('kind', 'release')->where('active', true)->orderBy('id')->first();
    }

    public function available(): bool
    {
        return Schema::hasTable('shelter_document_templates') && Schema::hasTable('shelter_documents');
    }

    public function defaults(Contract $contract): array
    {
        $info = $contract->info;
        $maid = $contract->maid;
        $date = static function ($value) {
            if (!$value) return '';
            try { return Carbon::parse($value)->format('Y-m-d'); }
            catch (\Throwable $e) { return ''; }
        };
        $common = [
            'contract_number' => (string) $contract->id,
            'worker_name' => $info?->manual_maid_name ?: ($maid?->name ?? ''),
            'nationality' => $info?->manualMaidNationality?->title ?: ($maid?->nationality?->title ?: ($maid?->nationality?->sub_title ?? '')),
            'passport' => $info?->manual_maid_passport_num ?: ($maid?->passport_num ?? ''),
            'worker_id' => $maid?->id_number ?: ($maid?->border_number ?? ''),
            'entry_date' => $date($info?->maid_date_back) ?: now()->format('Y-m-d'),
            'employee_name' => auth('admin')->user()?->name ?? '',
        ];
        return [
            'entry' => $common + [
                'date' => $common['entry_date'],
                'client_name' => $contract->user?->name ?? '',
                'client_id' => $contract->user?->documentNumber() ?? '',
                'client_phone' => $contract->user?->phone ?? '',
                'arrival_date' => $date($info?->arrival_date ?: ($maid?->arrival_date ?: $contract->travelHistory?->arrival_date)),
                'details' => $info?->back_reasons ?? '',
            ],
            // A release date or recipient is not inferred from the admission/client.
            'release' => $common,
        ];
    }

    public function editor(?Contract $contract): array
    {
        if (!$this->available()) return ['templates' => collect(), 'documents' => collect(), 'defaults' => []];
        $documents = $contract ? ShelterDocument::where('contract_id', $contract->id)->get()->keyBy('kind') : collect();
        return [
            'templates' => ShelterDocumentTemplate::where('active', true)->orderBy('company_name')->orderBy('name')->get(),
            'documents' => $documents,
            'default_release_template_id' => $this->defaultReleaseTemplate()?->id,
            'defaults' => $contract ? $this->defaults($contract) : [],
        ];
    }

    // Called inside the contract transaction, after its normal authorization checks.
    public function save(Contract $contract, Request $request): void
    {
        if (!$request->has('shelter_documents')) return;
        if (!$this->available()) {
            throw ValidationException::withMessages(['shelter_documents' => 'يلزم تحديث قاعدة البيانات قبل حفظ النماذج.']);
        }
        $rules = ['shelter_documents' => 'array'];
        foreach (ShelterDocumentFields::kinds() as $kind => $label) {
            $prefix = "shelter_documents.$kind";
            $rules[$prefix] = 'sometimes|array';
            $rules["$prefix.template_id"] = 'nullable|integer';
            $rules["$prefix.data"] = 'sometimes|array';
            foreach (ShelterDocumentFields::fields($kind) as $key => $field) {
                $path = "$prefix.data.$key";
                $rules[$path] = match ($field[1]) {
                    'date' => 'nullable|date_format:Y-m-d',
                    'time' => 'nullable|date_format:H:i',
                    'choices' => 'nullable|array',
                    'textarea' => 'nullable|string|max:5000',
                    default => 'nullable|string|max:255',
                };
                if ($field[1] === 'choices') $rules["$path.*"] = ['string', Rule::in(array_keys($field[2]))];
            }
        }
        $request->validate($rules);
        $defaults = $this->defaults($contract);
        foreach (ShelterDocumentFields::kinds() as $kind => $label) {
            if (!$request->has("shelter_documents.$kind")) continue;
            $document = ShelterDocument::where('contract_id', $contract->id)->where('kind', $kind)->lockForUpdate()->first();
            $templateId = $request->input("shelter_documents.$kind.template_id");
            if (!$templateId && $kind === 'release') {
                $templateId = $this->defaultReleaseTemplate()?->id;
            }
            if (!$templateId) {
                if ($document) $document->update(['enabled' => false]);
                continue;
            }
            $sameTemplate = $document && (int) $document->template_id === (int) $templateId;
            if (!$sameTemplate) {
                $template = ShelterDocumentTemplate::where('kind', $kind)->where('active', true)->find($templateId);
                if (!$template) throw ValidationException::withMessages([
                    "shelter_documents.$kind.template_id" => 'النموذج غير متاح أو لا يطابق نوع المستند.',
                ]);
            }
            $input = $request->input("shelter_documents.$kind.data", []);
            $data = array_replace($sameTemplate ? $document->data : $defaults[$kind], $input);
            foreach (ShelterDocumentFields::fields($kind) as $key => $field) {
                if ($field[1] === 'choices' && $request->has("shelter_documents.$kind.data")) $data[$key] = $input[$key] ?? [];
            }
            $data = array_intersect_key($data, ShelterDocumentFields::fields($kind));
            ShelterDocument::updateOrCreate(['contract_id' => $contract->id, 'kind' => $kind], [
                'template_id' => $templateId,
                'enabled' => true,
                'data' => $data,
                'revision' => (string) \Illuminate\Support\Str::uuid(),
            ]);
        }
    }
}
