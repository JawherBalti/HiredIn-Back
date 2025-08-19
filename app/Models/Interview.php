<?php
    namespace App\Models;

    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;

    class Interview extends Model
    {
        use HasFactory;

        protected $fillable = [
            'resume_id',
            'scheduled_by',
            'scheduled_time',
            'location',
            'notes',
            'status'
        ];

        public function resume()
        {
            return $this->belongsTo(Resume::class);
        }

        public function scheduler()
        {
            return $this->belongsTo(User::class, 'scheduled_by');
        }
    }