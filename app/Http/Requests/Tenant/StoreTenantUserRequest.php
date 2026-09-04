<?php

namespace App\Http\Requests\Tenant;

use App\Models\Tenant\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('tenant.users', 'email'),
            ],
            'password' => ['required', 'string', 'confirmed', 'max:255', Password::defaults()],
            'active' => ['required', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => trans('ui.tenantUsers.name'),
            'email' => trans('ui.tenantUsers.email'),
            'password' => trans('ui.tenantUsers.password'),
            'active' => trans('ui.tenantUsers.active'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->string('name')->toString()),
            'email' => User::normalizeEmail($this->string('email')->toString()),
        ]);
    }
}
