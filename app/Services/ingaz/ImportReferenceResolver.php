<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;

/** Read-only, branch-scoped matching. Never choose the first ambiguous result. */
class ImportReferenceResolver
{
    public function resolve(string $type, array $row): array
    {
        if (in_array($type, ['musaned_transfers', 'external_office_dispatches'], true)) {
            return app(RecruitmentOperationsImportService::class)->resolve($type, $row);
        }
        $issues = $links = [];
        if (in_array($type, ['invoices', 'updated_invoices'], true)) {
            $groups = [
                'ingaz_delegation_id' => [IngazDelegation::class, 'تفويض', [
                    'reference_msaned_number' => 'msaned_contract_number', 'reference_ingaz_number' => 'ingaz_number', 'reference_visa_number' => 'visa_number']],
                'contract_id' => [Contract::class, 'عقد استقدام', [
                    'reference_msaned_number' => 'msaned_number', 'reference_visa_number' => 'visa_number', 'reference_contract_number' => 'id']],
                'client_id' => [User::class, 'عميل', ['reference_client_identity' => 'national_num', 'reference_client_phone' => 'phone']],
            ];
            $kind = trim((string) ($row['invoice_reference_type'] ?? ''));
            $allowed = ['تفويض' => 'ingaz_delegation_id', 'عقد استقدام' => 'contract_id', 'عميل' => 'client_id'];
            if ($kind !== '' && !isset($allowed[$kind])) $issues[] = 'نوع ربط الفاتورة غير مقبول: اختر تفويض أو عقد استقدام أو عميل.';
            if (isset($allowed[$kind])) {
                $accepted = array_keys($groups[$allowed[$kind]][2]);
                foreach (array_unique(array_merge(...array_map(fn ($group) => array_keys($group[2]), array_values($groups)))) as $field) {
                    if (filled($row[$field] ?? null) && !in_array($field, $accepted, true)) $issues[] = 'أحد أرقام الربط لا يناسب نوع الفاتورة المختار؛ استخدم أرقام نفس نوع المستند فقط.';
                }
            }
            $explicit = array_values(array_filter(array_keys($groups), fn ($key) => filled($row[$key] ?? null)));
            if (count($explicit) > 1) $issues[] = 'اختر مرجعاً واحداً فقط للفاتورة.';
            $candidates = [];
            foreach ($groups as $key => [$model, $label, $fields]) {
                if ($kind !== '' && ($allowed[$kind] ?? null) !== $key) {
                    if (filled($row[$key] ?? null)) $issues[] = 'نوع الربط يتعارض مع المعرّف الداخلي المرسل.';
                    continue;
                }
                if ($explicit && !in_array($key, $explicit, true)) continue;
                $supplied = array_filter($fields, fn ($column, $field) => filled($row[$field] ?? null), ARRAY_FILTER_USE_BOTH);
                if (!$supplied && !filled($row[$key] ?? null)) continue;
                [$record, $errors] = $this->match($model, $supplied, $row, $row[$key] ?? null, $label, true);
                // Shared numbers can belong to either document type; ask rather than guess.
                if ($record) $candidates[$key] = [$record, $label];
                if ($errors && ($kind !== '' || $explicit || str_contains(implode(' ', $errors), 'أكثر من سجل') || str_contains(implode(' ', $errors), 'تخص سجلات مختلفة'))) $issues = array_merge($issues, $errors);
            }
            if (count($candidates) > 1) $issues[] = 'الرقم يطابق عقد استقدام وتفويضاً؛ حدد نوع ربط الفاتورة.';
            elseif (count($candidates) === 1 && !$issues) {
                $key = array_key_first($candidates);
                [$record, $label] = $candidates[$key];
                $row[$key] = $record->id;
                $links[] = $this->describe($record, $label);
                if (blank($row['import_reference'] ?? null)) {
                    if ($key === 'client_id') $issues[] = 'الفاتورة العامة تحتاج رقم فاتورة خارجي أو مرجع استيراد ثابت لتمييز فواتير العميل.';
                    else $row['import_reference'] = 'AUTO-'.$key.'-'.$record->id;
                }
            } elseif (!$issues) $issues[] = 'لم يتم العثور على مرجع فريد للفاتورة. أدخل رقم مساند أو إنجاز أو هوية العميل، وحدد نوع الربط عند الحاجة.';
            return compact('row', 'issues', 'links');
        }

        // Normal templates already accept these business identifiers; validate conflicts
        // before their existing creation/update logic and show the match to the reviewer.
        $clientTypes = ['clients', 'ingaz_delegations', 'recruitment_contracts', 'recruitment_contracts_clients_maids',
            'rent_contracts', 'rent_contracts_clients_rental_maids', 'shelter_contracts', 'sponsorship_transfers'];
        if (in_array($type, $clientTypes, true)) {
            $fields = $type === 'clients' ? ['id_number' => 'national_num', 'phone' => 'phone']
                : ['client_id_number' => 'national_num', 'client_phone' => 'phone'];
            $id = $type === 'ingaz_delegations' ? ($row['existing_user_id'] ?? null) : ($type === 'clients' ? ($row['id'] ?? null) : ($row['client_id'] ?? null));
            [$client, $errors] = $this->match(User::class, $fields, $row, $id, 'العميل');
            $issues = array_merge($issues, $errors);
            if ($client) {
                $links[] = $this->describe($client, 'العميل');
                if ($type === 'ingaz_delegations') $row['existing_user_id'] = $client->id;
                $known = $type === 'clients' ? ['name' => $client->name, 'phone' => $client->phone, 'id_number' => $client->national_num]
                    : ['client_name' => $client->name, 'client_phone' => $client->phone, 'client_id_number' => $client->national_num];
                foreach ($known as $field => $value) if (blank($row[$field] ?? null) && filled($value)) $row[$field] = $value;
            }
        }
        $passportField = $type === 'ingaz_delegations' ? 'worker_passport_number' : ($type === 'rental_maids' ? 'passport_num' : 'maid_passport');
        if ($type === 'maids') $passportField = 'passport_num';
        if (filled($row[$passportField] ?? null)) {
            [$maid, $errors] = $this->match(Maid::class, [$passportField => 'passport_num'], $row, $type === 'maids' ? ($row['id'] ?? null) : null, 'العامل/العاملة');
            $issues = array_merge($issues, $errors);
            if ($maid) {
                $links[] = $this->describe($maid, 'العامل/العاملة');
                if (in_array($type, ['recruitment_contracts_clients_maids', 'rent_contracts_clients_rental_maids'], true)) {
                    if (blank($row['maid_name_en'] ?? null)) $row['maid_name_en'] = $maid->name;
                    if (blank($row['maid_nationality'] ?? null)) $row['maid_nationality'] = $maid->nationality?->title;
                }
            }
        }
        if ($type === 'ingaz_delegations') {
            [$record, $errors] = $this->match(IngazDelegation::class, ['ingaz_number' => 'ingaz_number', 'msaned_contract_number' => 'msaned_contract_number', 'visa_number' => 'visa_number'], $row, $row['id'] ?? null, 'التفويض');
            $issues = array_merge($issues, $errors);
            if ($record) $links[] = $this->describe($record, 'التفويض');
        }
        if (in_array($type, ['recruitment_contracts', 'recruitment_contracts_clients_maids'], true)) {
            $id = filled($row['contract_number'] ?? null) && Contract::query()->whereKey($row['contract_number'])->exists() ? $row['contract_number'] : null;
            [$record, $errors] = $this->match(Contract::class, ['msaned_number' => 'msaned_number', 'visa_number' => 'visa_number'], $row, $id, 'عقد الاستقدام');
            $issues = array_merge($issues, $errors);
            if ($record) {
                $links[] = $this->describe($record, 'عقد الاستقدام');
                if (filled($row['contract_number'] ?? null) && (string) $row['contract_number'] !== (string) $record->id) {
                    $issues[] = 'رقم العقد لا يطابق العقد الموجود برقم مساند أو التأشيرة؛ راجع الأرقام قبل الاعتماد.';
                } else $row['contract_number'] = $record->id;
            }
        }
        if (in_array($type, ['rent_contracts', 'rent_contracts_clients_rental_maids'], true)) {
            $field = $type === 'rent_contracts' ? 'contract_number' : 'rent_contract_number';
            [$record, $errors] = $this->match(\App\Models\RentalContract::class, [$field => 'contract_number'], $row, null, 'عقد الإيجار');
            $issues = array_merge($issues, $errors);
            if ($record) $links[] = ['label' => 'عقد الإيجار', 'id' => $record->id, 'name' => $record->user?->name, 'number' => $record->contract_number];
        }
        return compact('row', 'issues', 'links');
    }

    private function match(string $model, array $fields, array $row, $id, string $label, bool $required = false): array
    {
        $matches = [];
        $missing = [];
        foreach ($fields as $field => $column) {
            if (blank($row[$field] ?? null)) continue;
            $value = $this->normalize($row[$field]);
            $query = $model::query();
            if ($column === 'phone') {
                $phone = preg_replace('/\D/', '', $value);
                if (str_starts_with($phone, '00966')) $phone = substr($phone, 5);
                elseif (str_starts_with($phone, '966')) $phone = substr($phone, 3);
                $phone = ltrim($phone, '0');
                $query->whereIn('phone', [$phone, '0'.$phone, '966'.$phone, '+966'.$phone, '00966'.$phone]);
            } elseif ($column === 'passport_num') {
                $query->whereRaw('UPPER(TRIM(passport_num)) = ?', [mb_strtoupper($value)]);
            } elseif ($model === Contract::class && in_array($column, ['msaned_number', 'visa_number'], true)) {
                $query->where(fn ($q) => $q->where($column, $value)->orWhereHas('info', fn ($q) => $q->where($column, $value)));
            } else $query->where($column, $value);
            $found = $query->limit(2)->get();
            if ($found->count() > 1) return [null, ["{$label}: الرقم يطابق أكثر من سجل؛ صحح البيانات قبل الاعتماد."]];
            if ($found->isNotEmpty()) $matches[$found->first()->id] = $found->first();
            else $missing[] = $field;
        }
        if (filled($id)) {
            $found = $model::query()->find($id);
            if (!$found) return [null, ["{$label}: المعرّف الداخلي غير موجود أو خارج الفرع. اتركه فارغاً عند الإضافة واستخدم الرقم المعروف."]];
            $matches[$found->id] = $found;
        }
        if (count($matches) > 1) {
            $details = $model === User::class ? ' '.implode('؛ ', array_map(fn ($client) => $client->name.' (الهوية: '.$client->national_num.')', array_values($matches))) : '';
            return [null, ["{$label}: الأرقام المقدمة تخص سجلات مختلفة؛ لا يمكن الربط تلقائياً.".$details]];
        }
        if ($required && $missing && $matches) return [null, ["{$label}: الأرقام المقدمة تخص سجلات مختلفة أو لا تتطابق؛ راجع جميع أرقام الربط."]];
        if ($required && $missing) return [null, ["{$label}: أحد أرقام الربط غير موجود أو لا يطابق المرجع المختار."]];
        $record = reset($matches) ?: null;
        if ($record instanceof User) {
            foreach ($fields as $field => $column) {
                if ($column === 'national_num' && filled($row[$field] ?? null) && filled($record->national_num)
                    && $this->normalize($row[$field]) !== $this->normalize($record->national_num)) {
                    return [null, ['العميل: الهوية والجوال أو المعرّف تخص سجلات مختلفة. الجوال '.$record->phone
                        .' مرتبط بالعميل «'.$record->name.'» وهوية '.$record->national_num
                        .'، بينما هوية الصف '.$this->normalize($row[$field]).'. راجع الجوال أو الهوية.']];
                }
            }
        }
        if ($record instanceof Maid && filled($row['passport_num'] ?? null) && mb_strtoupper($this->normalize($row['passport_num'])) !== mb_strtoupper($this->normalize($record->passport_num))) {
            return [null, ['العامل/العاملة: المعرّف الداخلي لا يطابق رقم الجواز.']];
        }
        return [$record, []];
    }

    private function describe($record, string $label): array
    {
        $client = $record instanceof Contract || $record instanceof IngazDelegation ? $record->user : null;
        return ['label' => $label, 'id' => $record->id, 'name' => $client?->name ?? $record->name,
            'number' => $record->msaned_contract_number ?? $record->msaned_number ?? $record->passport_num ?? $record->national_num];
    }

    private function normalize($value): string
    {
        return trim(strtr((string) $value, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9', "\xc2\xa0"=>' ']));
    }
}
