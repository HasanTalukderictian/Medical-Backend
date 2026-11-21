<?php

namespace App\Http\Requests\Admin;

class UpdateRoleRequest extends StoreRoleRequest
{
    protected function roleId(): ?int
    {
        return $this->route('role') ? $this->route('role')->id : null;
    }
}
