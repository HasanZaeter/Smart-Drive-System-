<?php

namespace App\Http\Requests;

use App\Helpers\JsonResponseHelper;
use App\Repositories\PermissionRepository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateFolderRequest extends FormRequest
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

        $permissionRepo = app(PermissionRepository::class);

        if ($permissionRepo->userHasPermission($authUser, $folder->id, 3)) {
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
            'parent_id' => 'sometimes|exists:folders,id|nullable',
            'name' => 'sometimes',
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
            JsonResponseHelper::errorResponse("you haven't authorization to update this folder", [], 403)
        );
    }
}
