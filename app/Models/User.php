<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function country(){
        return $this->belongsTo(Country::class);
    }


    public function region(){
        return $this->belongsTo(Region::class);
    }


    public function state(){
        return $this->belongsTo(State::class);
    }


    public function lga(){
        return $this->belongsTo(LocalGovernmentArea::class);
    }


    public function ward(){
        return $this->belongsTo(Ward::class);
    }


    public function pollingUnit(){
       return $this->belongsTo(PollingUnit::class);
    }

    public function religion(){
        return $this->belongsTo(Religion::class);
    }

    public function supportGroup(){
        return $this->belongsTo(SupportGroup::class);
    }


    public function ageGrade(){
        return $this->belongsTo(AgeGrade::class);
    }


}
