<?php
// app/Models/Resume.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'job_offer_id',
        'file_path',
        'file_name',
        'status',
        'cover_letter'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobOffer()
    {
        return $this->belongsTo(JobOffer::class);
    }
    
    public function interview()
    {
        return $this->hasOne(Interview::class);
    }
}