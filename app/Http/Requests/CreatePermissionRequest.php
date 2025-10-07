<?php

namespace App\Http\Requests;

use App\Helpers\JsonResponseHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;


class CreatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $folder = $this->route('folder');
        $ownerFolder = $folder->user_id;

        $authUser = Auth::user()->id;
        if ($authUser === $ownerFolder) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            // 'folder_id' => 'required|exists:folders,id',
            'permission_id' => 'required|exists:permissions,id'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = collect($validator->errors()->toArray())
            ->map(fn($error) => $error[0]) // Get only the first error for each field
            ->toArray();

        throw new HttpResponseException(
            JsonResponseHelper::errorResponse('Validation Error', $errors, 400)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            JsonResponseHelper::errorResponse("you can't assign permissions on other user's folders", [], 403)
        );
    }
}
