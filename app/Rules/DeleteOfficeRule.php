<?php

namespace App\Rules;

use App\Http\Controllers\ingaz\RecruitmentOffice;
use App\Models\office\Office;
use App\Models\RecruitmentOffices;
use Illuminate\Contracts\Validation\Rule;

class DeleteOfficeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $officeId;

    public function __construct($officeId)
    {
        $this->officeId = $officeId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $office = Office::with(['maids.contracts'])->find($this->officeId);
        if (! $office) {
            return false;
        }
        $hasMaids = $office->maids()->exists();

        $hasContracts = $office->maids()->whereHas('contracts')->exists();

        return ! $hasMaids && ! $hasContracts;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __( __("ingaz.delete_office_validation_Error"));
    }
}
