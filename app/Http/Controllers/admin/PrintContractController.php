<?php

namespace App\Http\Controllers\admin;

use App\Models\Contract;
use App\Models\NewSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PrintContractController extends Controller
{
    public function index()
    {
        $fields = [
            'contract_template',
        ];

        $settings = new NewSettings();
        $data = [];

        foreach ($fields as $field) {
            $data[$field] = $settings->getSetting($field);
        }
        return view('admin.prints.contract_template', compact('data'));
    }

    public function contract($contractId)
    {
        $data = Contract::find($contractId);
        if (!$data) {
            return redirect()->back();
        }
        $info = getContractInfo($contractId);

        $contractDuration = @$info->contract_duration ?? 0;
        $contractDate = Carbon::parse(@$info->contract_date);
        $info['contract_expire_date'] = $contractDate ? date('Y-m-d', strtotime($contractDate . ' + ' . $contractDuration . ' days')) : '-------------';

        ////////////////////////////////////////
        $search = [
            "@app_name",
            "@todays_date",
            "@client_name",
            "@client_nationality",
            "@client_national_number",
            "@client_address",
            "@client_phone_code",
            "@client_phone_number",
            "@maid_nationality",
            "@maid_passport_num",
            "@maid_experience_years",
            "@contract_working_hours_num",
            "@contract_duration",
            "@contract_monthly_cost",
            "@contract_date",
            "@contract_expire_date",
        ];
        $replace = [
            'شركة بلاتنيوم الشرقية للإستقدام',
            Carbon::now()->format('d/m/Y') ?? '----------',
            $data->user->name ?? '----------',
            getNationalityName(@$data->user->nationality_id) ?? '----------',
            $data->user->national_num ?? '----------',
            $data->user->address ?? '----------',
            $data->user->phone_code ?? '----------',
            $data->user->phone ?? '----------',
            getNationalityName(@$data->maid->nationality_id) ?? '----------',
            $data->maid->passport_num ?? '----------',
            $data->maid->experience_years ?? '----------',
            $info->working_hours_num  ?? '----------',
            $info->contract_duration  ?? '----------',
            $info->monthly_cost ?? '----------',
            $info->contract_date ?? '----------',
            $info->contract_expire_date ?? '----------',
        ];
        $settings = new NewSettings();
        $pageContent = $settings->getSetting('contract_template') ?? 'لا يوجد محتوى عقد مدخل حاليا';
        $outputContentString = str_replace($search, $replace, $pageContent);
        ////////////////////////////////////////
        return self::renderTemplate(
            compact(
                'data',
                'outputContentString',
            )
        );
    }
    public function renderTemplate($compact)
    {
        return view('admin.prints.contract', $compact);
    }

    public function store(Request $request)
    {
        $fields = [
            'contract_template',
        ];
        $settings = new NewSettings();
        foreach ($fields as $field) {
            if (isset($request->$field)) {
                $value = $request->$field ?? '';
                $settings->setSetting($field, $value);
            }
        }
        return redirect()->back();
    }
}
