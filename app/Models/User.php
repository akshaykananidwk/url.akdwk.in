<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, \Illuminate\Auth\MustVerifyEmail;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'locale', 'timezone', 'theme',
        'plan_id', 'plan_cycle', 'plan_expires_at', 'trial_ends_at',
        'referral_code', 'referred_by', 'default_domain', 'notification_prefs',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'plan_expires_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'notification_prefs' => 'array',
            'affiliate_balance' => 'decimal:2',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Relations                                                           */
    /* ------------------------------------------------------------------ */

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function pixels(): HasMany
    {
        return $this->hasMany(Pixel::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    public function bioPages(): HasMany
    {
        return $this->hasMany(BioPage::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'owner_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'user_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function utmTemplates(): HasMany
    {
        return $this->hasMany(UtmTemplate::class);
    }

    public function alertChannels(): HasMany
    {
        return $this->hasMany(AlertChannel::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'staff'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Staff permission check; super admins pass everything. */
    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities) && str_starts_with($abilities, 'admin.')) {
            return $this->role === 'admin'
                || ($this->role === 'staff' && in_array($abilities, $this->permissions ?? [], true));
        }

        return parent::can($abilities, $arguments);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /** The effective plan (falls back to the default/free plan when expired or unset). */
    public function currentPlan(): Plan
    {
        if ($this->plan && ! $this->planExpired()) {
            return $this->plan;
        }

        return Plan::defaultPlan();
    }

    public function planExpired(): bool
    {
        if ($this->plan_cycle === 'lifetime') {
            return false;
        }
        if ($this->plan && $this->plan->is_free) {
            return false;
        }

        return $this->plan_expires_at !== null && $this->plan_expires_at->isPast();
    }

    public function avatarUrl(): string
    {
        if ($this->avatar) {
            return Storage::disk(setting('storage_disk', 'public'))->url($this->avatar);
        }

        return 'https://www.gravatar.com/avatar/' . md5(strtolower($this->email)) . '?d=mp&s=160';
    }

    public function ensureReferralCode(): string
    {
        if (! $this->referral_code) {
            $this->forceFill(['referral_code' => Str::lower(Str::random(8))])->save();
        }

        return $this->referral_code;
    }

    /** Workspace owners whose team this user belongs to (accepted invites only). */
    public function workspaces()
    {
        return User::whereIn('id', $this->memberships()->whereNotNull('accepted_at')->pluck('owner_id'));
    }
}
