<?php

namespace App\Models;

use App\Keys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShelterWorkers extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'city',
        'worker_name',
        'residence_num',
        'passport_num',
        'nationality_id',
        'shelter_status',
        'received_by',
        'received_by_other',//null
        'status',
        'worker_type',//null
            //** Added D1M9Y24 */
        Keys::travel_doc,
        Keys::travel_doc_notes,
        Keys::settlement,
        Keys::settlement_notes,
        Keys::graduation,
        Keys::graduation_notes,
        Keys::reserve,
        'reserve_date',
        Keys::reserve_notes,
        Keys::travel,
        Keys::travel_notes,
            //** Added D1M9Y24 */
        Keys::contract_id,


    ];


}
