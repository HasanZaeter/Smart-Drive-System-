<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\JsonResponseHelper;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SearchFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'created_at' => 'sometimes|date',
            'updated_at' => 'sometimes|date',
            'extension' => 'sometimes',
            'file_name' => 'sometimes',
            'folder_id' => 'sometimes|exists:folders,id',
            'small_size' => 'sometimes',
            'big_size' => 'sometimes',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = collect($validator->errors()->toArray())
            ->map(fn($error) => $error[0])
            ->toArray();

        throw new HttpResponseException(
            JsonResponseHelper::errorResponse('Validation Error', $errors, 400)
        );
    }
}
