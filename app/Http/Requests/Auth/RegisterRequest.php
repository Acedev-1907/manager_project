<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $message = 'Validation failed';
        if ($errors->has('email') && in_array('The email has already been taken.', $errors->get('email'))) {
            $message = __('validationMessages.email_unique');
        }
        throw new HttpResponseException(response()->json([
            'message' => $message,
            'errors' => $errors
        ], 422));
    }
}
