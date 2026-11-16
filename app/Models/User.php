<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'organization_id', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'organizer', 'user'];

    public const ROLE_LABELS = ['admin' => 'Admin', 'organizer' => 'Penyelenggara', 'user' => 'Pengguna'];

    protected $attributes = ['role' => 'user'];

    /** Akun yang baru mendaftar menerima email selamat datang, bukan email verifikasi biasa. */
    private bool $welcomeEmailPending = false;

    public function markAsNewlyRegistered(): static
    {
        $this->welcomeEmailPending = true;

        return $this;
    }

    public function sendEmailVerificationNotification(): void
    {
        $notification = $this->welcomeEmailPending ? new WelcomeVerifyEmail : new VerifyEmailNotification;
        $this->welcomeEmailPending = false;

        $this->notify($notification);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))->filter()->take(2)
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
    }

    public function hasVerifiedOrganization(): bool
    {
        return (bool) $this->organization?->isVerified();
    }

    /** Boleh membuka dashboard penyelenggara (punya organisasi, punya kegiatan lama, atau admin). */
    public function canManageEvents(): bool
    {
        return $this->isAdmin() || $this->organization_id !== null || $this->events()->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }
}
