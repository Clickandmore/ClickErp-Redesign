<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Services\AccountService;

class TaxSettingService extends MainService
{
    public function __construct(TaxSetting $model,protected AccountService $accountService)
    {
        $this->model = $model;
    }

    public function update($id, $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $actor = (int) (auth('admin')->id() ?: $this->find($id)->created_by_id);
            $resolver = app(TaxAccountResolver::class);
            foreach (TaxAccountResolver::RATE_SETTINGS as $side => $title) {
                $resolver->remember($side, $actor);
            }
            $result = parent::update($id, $data);
            foreach (TaxAccountResolver::RATE_SETTINGS as $side => $title) {
                $resolver->remember($side, $actor);
            }
            return $result;
        });
    }

    public function delete($id)
    {
        $tax = $this->find($id);
        if ($tax->hasChildrens()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tax_setting' => 'لا يمكن حذف ضريبة مستخدمة في الإعدادات أو العمليات المالية.',
            ]);
        }

        return $tax->delete();
    }

    public function getAccounts()
    {
        return $this->accountService->getAccountWithNoChild();
    }
}
