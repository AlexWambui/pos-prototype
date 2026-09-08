<?php

namespace App\Http\Requests\Branches;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
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
        $branch = $this->route('branch');
        $branch_id = $branch ? $branch->id : null;

        return [
            'name' => ['required', 'string', 'max:120', Rule::unique('branches')->ignore($branch_id)],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('branches', 'code')->ignore($branch_id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Branch name is required',
            'name.unique' => 'A branch with this name already exists.',
            'code.unique' => 'This branch code is already in use.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }
}
