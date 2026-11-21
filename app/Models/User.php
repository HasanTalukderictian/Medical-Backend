<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed', // Laravel 10: nije thekei Hash::make kore
        'active' => 'boolean',
    ];

    /**
     * User er role.
     * Naam "adminRole" karon onek project er users table e age thekei "role"
     * naame string column thake ("admin"), ar Laravel e column relation ke dheke dey.
     */
    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function isFullAccess(): bool
    {
        return (bool) ($this->adminRole && $this->adminRole->isFullAccess());
    }

    /** User er shob permission key */
    public function permissionList(): array
    {
        return $this->adminRole ? $this->adminRole->permissionList() : [];
    }

    /** hasPermission('users') ba hasPermission('users', 'settings') -> jekono ekta thaklei true */
    public function hasPermission(string ...$keys): bool
    {
        if ($this->isFullAccess()) {
            return true;
        }

        return count(array_intersect($keys, $this->permissionList())) > 0;
    }

    /** Login / me response e je user data jabe (frontend eta save kore) */
    public function toAuthPayload(): array
    {
        $this->loadMissing('adminRole');
        $role = $this->adminRole;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $role ? $role->slug : null,
            'role_name' => $role ? $role->name : null,
            'active' => (bool) $this->active,
            'permissions' => $this->permissionList(),
        ];
    }
}
