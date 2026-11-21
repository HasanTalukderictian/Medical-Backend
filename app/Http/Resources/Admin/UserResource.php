<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'role' => $this->whenLoaded('adminRole', fn () => $this->adminRole ? [
                'id' => $this->adminRole->id,
                'name' => $this->adminRole->name,
                'slug' => $this->adminRole->slug,
            ] : null),
            'active' => (bool) $this->active,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
