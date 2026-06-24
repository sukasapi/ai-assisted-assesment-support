<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'ais_pengguna';

    protected $authPasswordName = 'kata_sandi';

    protected $rememberTokenName = 'token_ingat';

    public const CREATED_AT = 'dibuat_pada';

    public const UPDATED_AT = 'diperbarui_pada';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'alamat_surel',
        'kata_sandi',
        'peran',
        'aktif',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'kata_sandi',
        'token_ingat',
    ];

    protected function casts(): array
    {
        return [
            'diverifikasi_pada' => 'datetime',
            'kata_sandi' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    public function isKonsultan(): bool
    {
        return $this->peran === 'konsultan';
    }

    /**
     * Apakah peran pengguna termasuk salah satu yang diberikan.
     * Sumber otoritatif untuk otorisasi (tanpa accessor `role` yang deprecated).
     */
    public function hasPeran(string ...$peran): bool
    {
        return in_array((string) $this->peran, $peran, true);
    }

    public function consultantAssignments(): HasMany
    {
        return $this->hasMany(ConsultantAssignment::class, 'id_pengguna');
    }

    /**
     * Kompatibilitas paket yang mengharapkan atribut `email`.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->alamat_surel,
            set: fn (?string $value): array => ['alamat_surel' => $value],
        );
    }

    /** @deprecated Gunakan `nama` */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attributes['nama'] ?? null,
            set: fn (?string $value): array => ['nama' => $value],
        );
    }

    /** @deprecated Gunakan `kata_sandi` */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attributes['kata_sandi'] ?? null,
            set: fn (?string $value): array => ['kata_sandi' => $value],
        );
    }

    /** @deprecated Gunakan `token_ingat` */
    protected function rememberToken(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attributes['token_ingat'] ?? null,
            set: fn (?string $value): array => ['token_ingat' => $value],
        );
    }

    /** @deprecated Gunakan `diverifikasi_pada` */
    protected function emailVerifiedAt(): Attribute
    {
        return Attribute::make(
            get: fn (): mixed => $this->attributes['diverifikasi_pada'] ?? null,
            set: fn (mixed $value): array => ['diverifikasi_pada' => $value],
        );
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->alamat_surel;
    }
}
