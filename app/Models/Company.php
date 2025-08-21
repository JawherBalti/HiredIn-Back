<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'website',
        'industry',
        'description',
        'logo_url',
        'logo_public_id'
    ];

    protected $hidden = [
        'logo_public_id' // Hide this from API responses if needed
    ];
    
    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class);
    }
}   