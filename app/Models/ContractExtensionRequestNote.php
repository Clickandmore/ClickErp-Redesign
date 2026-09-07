<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class ContractExtensionRequestNote extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, BranchScoped;

    protected $fillable = ['extension_request_id', 'admin_id', 'branch_id', 'note'];

    protected $casts = ['branch_id' => 'integer'];

    public function extensionRequest()
    {
        return $this->belongsTo(ContractExtensionRequest::class, 'extension_request_id');
    }

    public function admin()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'admin_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromAdmin($this->admin_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
