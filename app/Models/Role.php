<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'permissions', 'is_system'];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    /** Super Admin / Admin: shob kichu pay */
    public function isFullAccess(): bool
    {
        return in_array($this->slug, config('permissions.full_access_roles', []), true);
    }

    /** Ei role er permission list (full access hole shob key) */
    public function permissionList(): array
    {
        if ($this->isFullAccess()) {
            return config('permissions.keys', []);
        }

        return array_values(array_intersect(
            $this->permissions ?? [],
            config('permissions.keys', [])
        ));
    }

    /** "Content Editor" -> "content_editor" (eki naam thakle _2, _3 ...) */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '_') ?: 'role';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'_'.$i++;
        }

        return $slug;
    }
}
