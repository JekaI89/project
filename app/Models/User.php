<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'login',
        'password',
        'product_user_id',
        'podr_id',
        'podr_name',
        'is_active',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'product_user_id' => 'integer',
            'podr_id' => 'integer',
        ];
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    /**
     * Mirror of Delphi TUser.get_pravo(pravo_id, podr_id).
     * podr_id = -1 means "any / global" in legacy code.
     */
    public function hasPravo(int $pravoId, ?int $podrId = -1): bool
    {
        if ($this->is_admin) {
            return true;
        }

        $query = $this->permissions()->where('pravo_id', $pravoId);

        if ($podrId === null || $podrId === -1) {
            return $query->where(function ($q) {
                $q->whereNull('podr_id')->orWhere('podr_id', -1);
            })->exists() || $this->permissions()->where('pravo_id', $pravoId)->exists();
        }

        return $query->where(function ($q) use ($podrId) {
            $q->whereNull('podr_id')
                ->orWhere('podr_id', -1)
                ->orWhere('podr_id', $podrId);
        })->exists();
    }

    public function preference(string $key, mixed $default = null): mixed
    {
        $row = $this->preferences()->where('key', $key)->first();

        if (! $row) {
            return $default;
        }

        return $row->value_json ?? $row->value_text ?? $default;
    }
}
