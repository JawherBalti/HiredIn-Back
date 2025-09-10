<?php
// app/Notifications/ApplicationAccepted.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use App\Models\Resume;
use App\Models\User;
use App\Events\Notificationsent;


class ApplicationAccepted extends Notification 
{
    use Queueable;

    protected $resume;
    protected $status;

    public function __construct(Resume $resume, $status)
    {
        $this->resume = $resume;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        $reciever = User::find($this->resume->user_id);
        $sender = User::find(auth()->id());

        broadcast(new Notificationsent($reciever, $sender, 'Your application for ' . $this->resume->jobOffer->title . ' has been ' . $this->status));
        
        return [
            'job_offer_id' => $this->resume->jobOffer->id,
            'job_offer_title' => $this->resume->jobOffer->title,
            'company_name' => $this->resume->jobOffer->company->name,
            'message' => 'Your job application for ' . $this->resume->jobOffer->title .  ' has been ' . $this->status . '.',
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'job_offer_id' => $this->resume->jobOffer->id,
            'job_offer_title' => $this->resume->jobOffer->title,
            'company_name' => $this->resume->jobOffer->company->name,
            'message' => 'Your job application has been ' . $this->status . '.',
        ]);
    }
}
