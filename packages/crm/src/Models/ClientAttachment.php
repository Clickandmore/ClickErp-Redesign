<?php

namespace Nami\Crm\Models;

use App\Models\ingaz\User as ClientUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAttachment extends Model
{
    use HasFactory;

    protected $table = 'crm_clients_attachments';
    protected $fillable = ['title', 'attachment_id', 'client_id', 'user_id', 'file'];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(ClientUser::class, 'user_id');
    }

    public function attachment()
    {
        return $this->belongsTo(AttachmentCategory::class, 'attachment_id');
    }

    public function getClientDisplayNameAttribute(): string
    {
        return $this->user?->name
            ?? $this->client?->client?->name
            ?? $this->client?->name
            ?? '';
    }
}
