<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'no_tlp',
        'foto_profil',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['foto_profil_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getFotoProfilUrlAttribute(): ?string
    {
        if (!$this->foto_profil) return null;
        // jika sudah URL absolut, kembalikan apa adanya
        if (str_starts_with($this->foto_profil, 'http')) return $this->foto_profil;
        return Storage::disk('public')->url($this->foto_profil);
    }
}
