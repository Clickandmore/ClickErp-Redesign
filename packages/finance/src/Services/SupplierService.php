<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Supplier as Model;
use Nami\Finance\Models\SupplierType;
use RuntimeException;

class SupplierService extends MainService
{
    function __construct(Model $model,protected AccountService $accountService, protected SupplierFileService $supplierFileService)
    {
        $this->model = $model;
        $this->fileFolder = 'fn_suppliers/';
        $this->files = ['national_address','tax_image','commercial_register_image'];
    }

    public function storeFiles($supplier_id, $data)
    {
        if (isset($data['file'])) {
            $this->supplierFileService->storeFiles($supplier_id, $data['file']);
        }
        return true;
    }

    public function deleteFiles($supplier_id)
    {
        $files = $this->supplierFileService->getWhere(['supplier_id' => $supplier_id]);
        if ($files) {
            foreach ($files as $file) {
                deleteFile($file->file);
                $file->delete();
            }
        }
    }


    public function storeSupplier($data)
    {
        $data += ['address' => '', 'email' => '', 'commercial_register_number' => 0, 'credit_limit' => 0];
        $data = $this->prepareSupplierData($data);
        $parent = $this->getParentOrFail();

        return DB::transaction(function () use ($data, $parent) {
            $account = $this->insertAccount($parent, $data["name"]);
            $supplier = parent::store($data + [
                "account_id" => $account->id,
                "code" => $account->code,
            ]);

            if (isset($data['file'])) {
                $this->storeFiles($supplier->id, $data);
            }

            return $supplier;
        });
    }

    public function updateSupplier($id, $data)
    {
        $data = $this->prepareSupplierData($data);
        $obj = $this->find($id);
        parent::update($id, $data);
        if (isset($data['file'])) {
            $this->storeFiles($id, $data);
        }
        $this->updateAccount($obj, $data);
        return $this->find($id);
    }

    public function getParent()
    {
        return $this->accountService->findByType(AccountTypesEnum::Suppliers->value);
    }

    public function getParentOrFail()
    {
        $parent = $this->getParent();

        if (!$parent) {
            $parent = $this->createParentAccount();
        }

        if (!$parent) {
            throw new RuntimeException(
                'لا يوجد حساب رئيسي للموردين في جدول الحسابات. يرجى إنشاء حساب من نوع Suppliers أولاً.'
            );
        }

        return $parent;
    }

    protected function createParentAccount()
    {
        return DB::transaction(function () {
            $existingParent = Account::query()
                ->where('type', AccountTypesEnum::Suppliers->value)
                ->lockForUpdate()
                ->first();

            if ($existingParent) {
                return $existingParent;
            }

            $shortLiabilitiesParent = Account::query()
                ->where('type', AccountTypesEnum::ShortLiabilities->value)
                ->lockForUpdate()
                ->first();

            if (!$shortLiabilitiesParent) {
                throw new RuntimeException(
                    'لا يوجد حساب رئيسي للخصوم قصيرة الأجل في جدول الحسابات، ولا يمكن إنشاء حساب الموردين تلقائيًا.'
                );
            }

            $parentPluckCode = $this->accountService->parentPluckCode();
            $childPluckCode = $this->accountService->childPluckCode($shortLiabilitiesParent->id);

            return $this->accountService->store([
                "name" => __("finance::finance.Suppliers"),
                "status" => $shortLiabilitiesParent->status,
                "statement_type" => $shortLiabilitiesParent->statement_type,
                "level" => ($shortLiabilitiesParent->level + 1),
                "type" => AccountTypesEnum::Suppliers->value,
                "parent_id" => $shortLiabilitiesParent->id,
                "code" => $this->accountService->getChildCode(
                    $shortLiabilitiesParent->id,
                    $childPluckCode,
                    $parentPluckCode
                ),
                "operation_type" => $shortLiabilitiesParent->operation_type,
                "created_by_id" => auth('admin')->id() ?? auth()->id() ?? 1,
            ]);
        });
    }

    public function insertAccount($parent,$name)
    {
        $parent = $parent ?: $this->getParentOrFail();

        return $this->accountService->store([
            "name" => $name,
            "status" => $parent->status,
            "level" => ($parent->level + 1),
            "type" => $this->getChildType($parent),
            "parent_id" => $parent->id,
            "code" => $this->getChildCode($parent),
        ]);
    }
    public function getChildType($parent)
    {
        return  $this->accountService->getChildType($parent);
    }
    public function getChildCode($parent)
    {
        $parent = $parent ?: $this->getParentOrFail();
        $parentPluckCode = $this->accountService->parentPluckCode();
        $childPluckCode = $this->accountService->childPluckCode($parent->id);
        return  $this->accountService->getChildCode($parent->id,$childPluckCode,$parentPluckCode);
    }

    public function updateAccount($item, $data)
    {
        return $this->accountService->update($item->account_id,
            [
                "name" => $data["name"],
            ]);
    }

    public function getAccountByID($supplier_id)
    {
        return $this->find($supplier_id)->account;
    }

    protected function prepareSupplierData(array $data): array
    {
        // These legacy columns are not nullable; blank optional inputs use empty defaults.
        foreach (['address' => '', 'email' => '', 'tax_number' => 0, 'commercial_register_number' => 0, 'credit_limit' => 0] as $field => $default) {
            if (array_key_exists($field, $data) && ($data[$field] === null || $data[$field] === '')) {
                $data[$field] = $default;
            }
        }

        $data['supplier_type_id'] = $this->resolveSupplierTypeId($data);
        unset($data['supplier_type_name']);

        return $data;
    }

    protected function resolveSupplierTypeId(array $data): int
    {
        $typeName = trim((string) ($data['supplier_type_name'] ?? ''));

        if ($typeName === '') {
            throw new RuntimeException('فئة المورد مطلوبة.');
        }

        if (isset($data['supplier_type_id']) && is_numeric($data['supplier_type_id'])) {
            $existingType = SupplierType::query()->find((int) $data['supplier_type_id']);

            if ($existingType) {
                return (int) $existingType->id;
            }
        }

        $type = SupplierType::query()->firstOrCreate(
            ['name' => $typeName],
            ['created_by_id' => auth('admin')->id() ?? auth()->id() ?? 1]
        );

        return (int) $type->id;
    }
}
