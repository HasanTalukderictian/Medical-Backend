<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission check route er 'permission:users' middleware e hoy
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:2', 'max:50',
                // Eki naam (boro / choto hater lekha alada holeo) duibar hobe na
                function ($attribute, $value, $fail) {
                    $exists = Role::whereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])
                        ->when($this->roleId(), fn ($q) => $q->where('id', '!=', $this->roleId()))
                        ->exists();
                    if ($exists) {
                        $fail('A role with this name already exists.');
                    }
                },
                // "Admin" / "Super Admin" naam nije banano jabe na (era full access role)
                function ($attribute, $value, $fail) {
                    if (in_array(Str::slug((string) $value, '_'), config('permissions.full_access_roles', []), true)) {
                        $fail('This role name is reserved.');
                    }
                },
            ],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'distinct', Rule::in(config('permissions.keys', []))],
        ];
    }

    /** UpdateRoleRequest e override hoy */
    protected function roleId(): ?int
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $this->input('name')))]);
        }
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'Tick at least one menu.',
            'permissions.min' => 'Tick at least one menu.',
            'permissions.*.in' => 'One of the selected menus is not valid.',
        ];
    }
}
