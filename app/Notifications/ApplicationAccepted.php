<?php
// app/Notifications/ApplicationAccepted.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\JobOffer;

class ApplicationAccepted extends Notification 
{
    use Queueable;

    protected $jobOffer;

    public function __construct(JobOffer $jobOffer)
    {
        $this->jobOffer = $jobOffer;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'job_offer_id' => $this->jobOffer->id,
            'job_offer_title' => $this->jobOffer->title,
            'company_name' => $this->jobOffer->company->name,
            'message' => 'Your job application has been accepted.',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'job_offer_id' => $this->jobOffer->id,
            'job_offer_title' => $this->jobOffer->title,
            'company_name' => $this->jobOffer->company->name,
            'message' => 'Your job application has been accepted.',
        ]);
    }
}
