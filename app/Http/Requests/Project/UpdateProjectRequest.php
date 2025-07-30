<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required',
            'name' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            'content' => 'nullable|string',
        ];
    }
}
