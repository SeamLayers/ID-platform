<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuditableContract
{
    use HasApiTokens, HasFactory, Notifiable, Auditable, HasRoles;

    /**
     * Use Sanctum's `api` guard for Spatie role/permission lookups.
     * Without this, `assignRole('owner')` and the `permission:*` middleware
     * resolve roles against the `web` guard and throw
     * "There is no role named X for guard `web`" — even when the role
     * exists under guard `api` (which is how the seeder creates them).
     */
    protected string $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'reset_otp',
        'otp_expires_at',
        'user_type', // (owner | employee | superadmin | external)
        'device_token', // FCM/APNs token persisted on each login.
        'phone',
        'ip_address',
    ];

    const TYPE_SUPERADMIN = 'superadmin';
    const TYPE_OWNER = 'owner';
    const TYPE_EMPLOYEE = 'employee';
    const TYPE_EXTERNAL = 'external';

    /*
     * Authentication → users table ONLY
     * Business Data → employees table
     */


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
    ];

      public function routeNotificationForFcm()
    {
        return $this->device_token;
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

}
