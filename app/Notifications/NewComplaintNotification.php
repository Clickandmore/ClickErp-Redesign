<?php

namespace App\Notifications;

use App\Models\ingaz\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewComplaintNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $complaint;

    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'complaint_id' => $this->complaint->id,
            'message' => 'تم استلام شكوى جديدة من ' . ($this->complaint->user ? $this->complaint->user->name : 'مستخدم'),
            'type' => 'new_complaint',
            'created_at' => now()
        ];
    }
}
