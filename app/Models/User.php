<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\HasLocationScope;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, HasLocationScope;

    /**
     * Explicitly define safe mass-assignable attributes to prevent privilege escalation.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'email',
        'password',
        'country_id',
        'region_id',
        'state_id',
        'senatorial_district_id',
        'federal_constituency_id',
        'lga_id',
        'ward_id',
        'polling_unit_id',
        'religion_id',
        'age_grade_id',
        'gender',
        'phone',
        'validVoter',
        'vin',
        'bank',
        'bank_account_number',
        'address',
        'occupation',
        'qualification',
        'photo',
        'cover_image',
        'access_level',
        'referral_code',
        'referred_by',
        'status',
        'requires_update',
    ];

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

    public function setGenderAttribute($value): void
    {
        $this->attributes['gender'] = self::normalizeGender($value);
    }

    public function getGenderAttribute($value): ?string
    {
        return self::normalizeGender($value);
    }

    public static function normalizeGender($value): ?string
    {
        $gender = strtolower(trim((string) $value));

        return in_array($gender, ['male', 'female'], true) ? $gender : null;
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

    public function pollingUnitAgentAssignments()
    {
        return $this->hasMany(PollingUnitAgentAssignment::class);
    }

    public function approvedPollingUnitAgentAssignments()
    {
        return $this->hasMany(PollingUnitAgentAssignment::class)->approved();
    }

    public function senatorialDistrict(){
        return $this->belongsTo(SenatorialDistrict::class);
    }

    public function federalConstituency(){
        return $this->belongsTo(FederalConstituency::class);
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

    public function smsWallet()
    {
        return $this->hasOne(SmsWallet::class);
    }

    public function emailNotificationCampaigns()
    {
        return $this->hasMany(EmailNotificationCampaign::class, 'sender_id');
    }

    public function emailNotificationRecipients()
    {
        return $this->hasMany(EmailNotificationRecipient::class, 'user_id');
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
            'senatorialadmin' => 'senatorial',
            'federaladmin' => 'federal',
            'lgaadmin' => 'lga',
            'wardadmin' => 'ward',
            'puadmin', 'user' => 'pu',
            default => 'public',
        };
    }

   /**
     * Finance Module.
     */

            public function transactions()
        {
            return $this->hasMany(Transaction::class);
        }

        public function expenses()
        {
            return $this->hasMany(Expense::class);
        }
      /**
     * Funding requests initiated by the user (receiver).
     */
    public function fundingRequests()
    {
        return $this->hasMany(FundingRequest::class, 'receiver_id');
    }

    /**
     * Funding requests approved by the user (admin).
     */
    public function approvedFundingRequests()
    {
        return $this->hasMany(FundingRequest::class, 'admin_id');
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


    public function referrals()
{
    return $this->hasMany(Referral::class, 'referrer_id');
}

public function invitedBy()
{
    return $this->belongsTo(User::class, 'referred_by');
}

public function invitees()
{
    return $this->hasMany(User::class, 'referred_by');
}


        protected static function booted()
        {
            static::creating(function ($user) {
                if (empty($user->referral_code)) {
                    $user->referral_code = self::generateReferralCode();
                }
            });
        }

        public static function generateReferralCode()
        {
            do {
                $code = strtoupper(Str::random(6));
            } while (self::where('referral_code', $code)->exists());

            return $code;
        }



        public function getReferralLinkAttribute()
        {
            return url('/register?ref=' . $this->referral_code);
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
