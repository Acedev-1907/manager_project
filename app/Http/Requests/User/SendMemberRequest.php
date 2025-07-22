<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SendMemberRequest extends FormRequest
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
        return [
            'receiver_id' => 'required|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.required' => __('validationMessages.receiver_id.required'),
            'receiver_id.exists' => __('validationMessages.receiver_id.exists'),
        ];
    }
}
