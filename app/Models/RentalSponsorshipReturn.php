<?php

namespace App\Models;

use App\Models\Concerns\BranchScoped;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Model;

class RentalSponsorshipReturn extends Model
{
    use BranchScoped;

    protected $guarded = ['id'];
    protected $casts = ['history' => 'array', 'request_date' => 'date'];

    public const LABELS = [
        'pending' => 'بانتظار الاعتماد', 'approved' => 'معتمد',
        'contract_sent' => 'تم إرسال العقد', 'contract_signed' => 'تم توقيع العقد',
        'received' => 'تم الاستلام بالإيواء', 'transferred' => 'اكتمل النقل للشركة',
        'cancelled' => 'ملغي',
    ];

    public const NEXT = [
        'pending' => 'approved', 'approved' => 'contract_sent',
        'contract_sent' => 'contract_signed', 'contract_signed' => 'received',
        'received' => 'transferred',
    ];

    public function sourceTransfer() { return $this->belongsTo(SponsorshipTransfer::class, 'source_transfer_id'); }
    public function contract() { return $this->belongsTo(Contract::class); }
    public function maid() { return $this->belongsTo(\App\Models\ingaz\Maid::class); }
    public function currentSponsor() { return $this->belongsTo(User::class, 'current_sponsor_id')->withTrashed(); }
    public function company() { return $this->belongsTo(CompanyProfile::class, 'company_profile_id'); }
    public function shelter() { return $this->belongsTo(ShelterPlaces::class, 'shelter_id'); }
}
