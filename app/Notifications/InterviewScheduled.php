<?php
    namespace App\Notifications;

    use App\Models\Interview;
    use Illuminate\Bus\Queueable;
    use Illuminate\Contracts\Queue\ShouldQueue;
    use Illuminate\Notifications\Messages\MailMessage;
    use Illuminate\Notifications\Notification;

    class InterviewScheduled extends Notification
    {
        use Queueable;

        public $interview;

        public function __construct(Interview $interview)
        {
            $this->interview = $interview;
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
                'location' => $this->interview->location
            ];
        }
        public function toDatabase($notifiable)
        {
            return [
                'interview_id' => $this->interview->id,
                'job_title' => $this->interview->resume->jobOffer->title,
                'scheduled_time' => $this->interview->scheduled_time,
                'location' => $this->interview->location,
                'message' => 'Your interview for a ' . $this->interview->resume->jobOffer->title . 'position position has beed scheduled for the ' . $this->interview->scheduled_time,
            ];
        }
    }