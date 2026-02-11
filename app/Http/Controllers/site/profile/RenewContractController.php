<?php

namespace App\Http\Controllers\site\profile;

use App\Enums\Office\MaritalStatusEnum;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Controllers\Controller;
use App\Models\RenewContract;
use Illuminate\Support\Facades\Auth;

class RenewContractController extends Controller
{
    public function index(NationalityAction $nationalityAction)
    {
        $nationalities = $nationalityAction->get();
        $maritalStatus = MaritalStatusEnum::cases();
        $renewContracts = RenewContract::with('nationality')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('site.pages.profile.renew_contracts', get_defined_vars());
    }
}
