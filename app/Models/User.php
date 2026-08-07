<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'profile_photo_path', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const ROLE_AUDITOR = 'auditor';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_USER,
        self::ROLE_AUDITOR,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isAuditor(): bool
    {
        return $this->role === self::ROLE_AUDITOR;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function hsmKeys(): HasMany
    {
        return $this->hasMany(HsmKey::class);
    }

    public function leafKeys(): HasMany
    {
        return $this->hasMany(LeafKey::class);
    }

    public function csrFiles(): HasMany
    {
        return $this->hasMany(CsrFile::class);
    }

    public function hsmCsrFiles(): HasMany
    {
        return $this->hasMany(HsmCsrFile::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Get the URL for the user's profile photo.
     */
    public function profilePhotoUrl(): ?string
    {
        if ($this->profile_photo_path && Storage::disk('public')->exists($this->profile_photo_path)) {
            $url = Storage::disk('public')->url($this->profile_photo_path);

            if (request()->header('X-Forwarded-Proto') === 'https' || str_contains(request()->getHost(), 'trycloudflare.com')) {
                
                $parsedUrl = parse_url($url);
                $path = $parsedUrl['path'] ?? '';
                $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
                
                return 'https://' . request()->getHost() . $path . $query;           
            }
            return $url;
        }

        return null;
    }

    /**
     * Get the initials for the user's name (used as a fallback avatar).
     */
    public function initials(): string
    {
        $name = trim($this->name);
        $parts = preg_split('/\s+/', $name) ?: [];

        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '?';
    }

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
}
