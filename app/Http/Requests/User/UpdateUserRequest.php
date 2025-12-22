<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string|max:255',
            'cover_photo' => 'sometimes|nullable|string|max:500',
            'password' => 'sometimes|string|min:6|confirmed',
            'password_confirmation' => 'sometimes|required_with:password',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.string' => __('validationMessages.name_string'),
            'name.max' => __('validationMessages.name_max'),
            'email.email' => __('validationMessages.email_email'),
            'email.unique' => __('validationMessages.email_unique'),
            'password.min' => __('validationMessages.password_min'),
            'password.confirmed' => __('validationMessages.password_confirmed'),
            'password_confirmation.required_with' => __('validationMessages.password_confirmation_required_with'),
        ];
    }
}
