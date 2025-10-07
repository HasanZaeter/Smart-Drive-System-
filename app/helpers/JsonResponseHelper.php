<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class JsonResponseHelper    
{
    public static function successResponse($message = '', $data = [], $statusCode = 200): JsonResponse
    {
        $responseData = [];

        $responseData['successful'] = true;
        if (! empty($message)) {
            $responseData['message'] = $message;
        }

        $responseData['data'] = $data;

        $responseData['status_code'] = $statusCode;

        return response()->json($responseData, $statusCode);
    }

    public static function errorResponse($message = '', $errors = [], $statusCode = 400): JsonResponse
    {
        $responseData = [];

        $responseData['successful'] = false;

        if (! empty($message)) {
            $responseData['message'] = $message;
        }

        if (! empty($errors)) {
            $responseData['errors'] = $errors;
        }

        $responseData['status_code'] = $statusCode;

        return response()->json($responseData, $statusCode);
    }
}
