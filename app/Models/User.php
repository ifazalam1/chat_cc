<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Billable;
use Stripe\Stripe;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable;

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'tour_progress' => 'array',
        'subscription_expires_at' => 'datetime',
    ];

    public function hasRole($role)
    {
        return $this->role === $role; // Assuming you have a 'role' column in your users table
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function sessions()
    {
    return $this->hasMany(Session::class);
    }

    // public function likedImages()
    // {
    //     return $this->belongsToMany(DalleImageGenerate::class, 'liked_images_dalles', 'user_id', 'image_id')->withTimestamps();
    // }

    // public function favoritedImages()
    // {
    //     return $this->belongsToMany(DalleImageGenerate::class, 'favorite_image_dalles', 'user_id', 'image_id')->withTimestamps();
    // }    

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referralCodes()
    {
        return $this->hasMany(ReferralCode::class);
    }
    
    public function brandTheme()
    {
        return $this->hasOne(BrandTheme::class);
    }

    public function packageHistory()
    {
        return $this->hasMany(PackageHistory::class);
    }

    public static function getPermissionGroups()
    {
        $permission_groups = DB::table('permissions')
            ->select('group_name')
            ->groupBy('group_name')
            ->get();

        return $permission_groups;
    }

    public static function getPermissionByGroupName($group_name)
    {
        $permissions = DB::table('permissions')
            ->select('name', 'id')
            ->where('group_name', $group_name)
            ->get();

        return $permissions;
    }

    public static function roleHasPermissions($role, $permissions)
    {
        $hasPermission = true;
        foreach ($permissions as $permission) {
            if (!$role->hasPermissionTo($permission->name)) {
                $hasPermission = false;
                break;
            }
        }

        return $hasPermission;
    }

    public function ratings()
    {
        return $this->hasMany(RatingTemplate::class);
    }

    // TOUR
    public function hasSeenStep($stepId)
    {
        return in_array($stepId, $this->tour_progress ?? []);
    }

    public function markStepAsSeen($stepId)
    {
        $progress = $this->tour_progress ?? [];
        if (!in_array($stepId, $progress)) {
            $progress[] = $stepId;
            $this->tour_progress = $progress;
            $this->save();
        }
    } 

    public function StableDiffusionlikes()
    {
        return $this->hasMany(StableDiffusionImageLike::class);
    }

    public function likedImages()
    {
        return $this->belongsToMany(GeneratedImage::class, 'image_likes')->withTimestamps();
    }

    public function favoriteImages()
    {
        return $this->belongsToMany(GeneratedImage::class, 'image_favorites')->withTimestamps();
    }

    // Tools Favorite
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }


    // NEW PRICING SYSTEM
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function hasActiveSubscription(): bool
   {
        $isActive = $this->subscription_status === 'active';
        $expiresFuture = $this->subscription_expires_at
            ? Carbon::parse($this->subscription_expires_at)->isFuture()
            : false;

        return $isActive && $expiresFuture;
    }

    public function hasFeature(string $feature): bool
    {

        if ($this->role === 'admin') {
            return true;
        }

        return $this->hasActiveSubscription()
            && $this->plan
            && $this->plan->features->contains('name', $feature);
    }

    public function aiModels(): array
    {
        $hasSub = $this->hasActiveSubscription();
        $planName = $this->plan?->name ?? 'none';
        $models = $this->plan?->ai_models ?? [];

        return $hasSub ? $models : [];
    }

    public function hasModelAccess(string $model): bool
    {
         if ($this->role === 'admin') {
            return true;
        }

        return in_array($model, $this->aiModels());
    }

    public function hasTemplateAccess(int|string $templateId): bool
    {

         if ($this->role === 'admin') {
            return true;
        }

        return $this->hasActiveSubscription()
            && in_array($templateId, $this->plan?->ai_templates ?? []);
    }

    public function hasExpertAccess(int|string $expertId): bool
    {

        if ($this->role === 'admin') {
            return true;
        }

        if (is_array($expertId)) {
            $expertId = $expertId[0];
        }

        return $this->hasActiveSubscription()
            && in_array($expertId, $this->plan?->experts ?? []);
    }
   
    public function hasEduToolAccess(int|string $toolId): bool
    {

        if ($this->role === 'admin') {
            return true;
        }

        return $this->hasActiveSubscription()
            && in_array($toolId, $this->plan?->edu_tools ?? []);
    }

}
