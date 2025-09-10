<?php
    namespace App\Notifications;

    use App\Models\User;
    use App\Models\Interview;
    use Illuminate\Bus\Queueable;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Notifications\Messages\MailMessage;
    use Illuminate\Notifications\Notification;
    use App\Events\Notificationsent;

    class InterviewScheduled extends Notification
    {
        use Queueable;

        public $interview;
        public $action; // 'scheduled' or 'updated'
        public $changes;

        public function __construct(Interview $interview, string $action = 'scheduled', array $changes = [])
        {
            $this->interview = $interview;
            $this->action = $action;
            $this->changes = $changes;
        }

        public function via($notifiable)
        {
            return ['database', 'broadcast'];
        }

        public function toArray($notifiable)
        {
            return [
                'interview_id' => $this->interview->id,
                'job_title' => $this->interview->resume->jobOffer->title,
                'scheduled_time' => $this->interview->scheduled_time,
                'location' => $this->interview->location,
                'status' => $this->interview->status,
                'action' => $this->action,
                'changes' => $this->changes
            ];
        }

        public function toDatabase($notifiable)
        {
            $message = 'Your interview for ' . $this->interview->resume->jobOffer->title;
            
            if ($this->action === 'scheduled') {
                $message .= ' has been scheduled for ' . $this->interview->scheduled_time;
            } else {
                $message .= ' has been updated';
                
                // Add specific change details for updates
                if ($this->changes['time_changed'] ?? false) {
                    $message .= '. Time: ' . $this->changes['original_time'] . ' → ' . $this->interview->scheduled_time;
                }
                
                if ($this->changes['location_changed'] ?? false) {
                    $originalLocation = $this->changes['original_location'] ?? 'Not specified';
                    $newLocation = $this->interview->location ?? 'Not specified';
                    $message .= '. Location: ' . $originalLocation . ' → ' . $newLocation;
                }
            }

            $reciever = User::find($this->interview->resume->user_id);
            $sender = User::find(auth()->id());

            broadcast(new Notificationsent($reciever, $sender, $message));


            return [
                'interview_id' => $this->interview->id,
                'job_title' => $this->interview->resume->jobOffer->title,
                'scheduled_time' => $this->interview->scheduled_time,
                'location' => $this->interview->location,
                'status' => $this->interview->status,
                'action' => $this->action,
                'changes' => $this->changes,
                'message' => $message
            ];
        }
    }