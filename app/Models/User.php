<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

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

    public function supportGroups()
    {
        return $this->belongsToMany(SupportGroup::class, 'user_support_group');
    }


    public function ageGrade(){
        return $this->belongsTo(AgeGrade::class);
    }

    public static function getPermissionGroups()
    {
        $permission_groups = DB::table('permissions')->select('group_name')->groupBy('group_name')->get();
        return $permission_groups;
    }

    public static function getPermissionByGroupName($group_name){
        $permissions = DB::table('permissions')->select('name','id')->where('group_name', $group_name)->get();
        return $permissions;
    }

    public static function roleHasPermissions($role, $permissions){
        $hasPermission = true;
        foreach($permissions as $permission){
            if(!$role->hasPermissionTo($permission->name)){
                $hasPermission = false;
            }
        }
        return $hasPermission;
    }



}
