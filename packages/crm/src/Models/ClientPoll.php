<?php

namespace Nami\Crm\Models;

use Nami\Crm\Models\Poll;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPoll extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    use HasFactory;
    protected $table = "crm_client_polls";
    protected $fillable = ["client_id", "poll_id", "status"];


    public function client()
    {
        return $this->belongsTo(Client::class,'client_id');
    }

    public function poll()
    {
        return $this->belongsTo(Poll::class,'poll_id');
    }



}
