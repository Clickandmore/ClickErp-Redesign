<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaidCvShare extends Model
{
    protected $guarded = [];
    protected $attributes = ['status' => 'draft'];
    protected $casts = ['confirmed_at' => 'datetime'];

    public function messages()
    {
        return $this->hasMany(MaidCvShareMessage::class, 'share_id')->orderBy('id');
    }

    public function refreshStatus(): void
    {
        $states = $this->messages()->pluck('status');
        $accepted = $states->filter(fn ($s) => in_array($s, ['accepted', 'sent', 'delivered', 'read'], true))->count();
        $status = match (true) {
            $states->contains('sending'), $states->contains('pending') => 'processing',
            $states->contains('unknown') => 'needs_review',
            $accepted === $states->count() && $states->isNotEmpty() => 'accepted',
            $accepted > 0 => 'partial',
            default => 'failed',
        };
        $this->update(['status' => $status]);
    }
}
