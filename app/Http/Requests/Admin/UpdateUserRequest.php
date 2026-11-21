<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Shob field "sometimes": shudhu je field ashe sheta bodlay.
 * Jemon status toggle e shudhu { active: 0 } ashe.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;

        return [
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            // Faka thakle password bodlabe na
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'role_id' => ['sometimes', 'required', 'integer', 'exists:roles,id'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim((string) $this->input('email')))]);
        }
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'The email has already been taken.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }
}
