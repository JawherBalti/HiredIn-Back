<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\JobOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Events\Notificationsent;

class JobApplied extends Notification
{
    use Queueable;

    protected $jobOffer;

    /**
     * Create a new notification instance.
     */
    public function __construct(JobOffer $jobOffer)
    {
        $this->jobOffer = $jobOffer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $reciever = User::find($this->jobOffer->user_id);
        $sender = User::find(auth()->id());

        broadcast(new Notificationsent($reciever, $sender,  $sender->name . ' applied for ' . $this->jobOffer->title . ' job offer.'));
        
        return [
            'job_offer_id' => $this->jobOffer->id,
            'job_offer_title' => $this->jobOffer->title,
            'company_name' => $this->jobOffer->company->name,
            'message' => $sender->name . ' applied for ' . $this->jobOffer->title . ' job offer.',
        ];
    }
}
