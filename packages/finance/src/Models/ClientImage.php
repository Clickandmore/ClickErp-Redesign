<?php

namespace Nami\Finance\Models;


class ClientImage extends Finance
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $table = 'client_iamages';

    protected $fillable = [
        'client_id',
        'image',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}
