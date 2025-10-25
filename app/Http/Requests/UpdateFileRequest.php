<?php

namespace App\Http\Requests;

use App\Helpers\JsonResponseHelper;
use App\Repositories\PermissionRepository;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class UpdateFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $file = $this->route('file');
        $ownerFile = $file->user_id;
        $authUserId = Auth::user()->id;
        if ($authUserId === $ownerFile) {
            return true;
        }
        $permissionRepo = app(PermissionRepository::class);

        return $permissionRepo->userHasPermission($authUserId, $file->folder_id, 2);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'folder_id' => 'sometimes|exists:folders,id',
            'file' => 'sometimes'
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

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            JsonResponseHelper::errorResponse("you haven't authorization to update this file", [], 403)
        );
    }
}
