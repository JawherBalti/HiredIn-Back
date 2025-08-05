<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'title', 'description', 
        'status', 'type', 'deadline', 'salary', 'location'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // app/Models/JobOffer.php
    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }
}