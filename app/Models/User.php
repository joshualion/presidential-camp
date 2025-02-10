<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function bulkSmsSetting()
    {
        return $this->hasOne(BulkSmsSetting::class, 'user_id');
    }

     // Define the one-to-many relationship between User and Post
     public function posts()
     {
         return $this->hasMany(Post::class);
     }

     /**
     * Get the users that are following this user.
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');

    }

    /**
     * Get the users this user is following.
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    // User.php
    public function follow(User $user)
    {
        if (!$this->isFollowing($user)) {
            $this->following()->attach($user->id);
        }
    }

    public function unfollow(User $user)
    {
        $this->following()->detach($user->id);
    }

    // User.php
    public function isFollowing(User $user)
    {
        return $this->following()->where('user_id', $user->id)->exists();
    }

    // In User.php (Model)
    public function getAudience()
    {
        return match ($this->access_level) {
            'superadmin', 'nationaladmin' => 'public',
            'regionaladmin' => 'region',
            'stateadmin' => 'state',
            'lgaadmin' => 'lga',
            'wardadmin' => 'ward',
            'puadmin', 'user' => 'pu',
            default => 'public',
        };
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


     //boot method for uuid

     public static function boot()
     {
         parent::boot();
         self::creating(function ($user) {
             $user->uuid = (string) Str::uuid();
         });
     }

}
