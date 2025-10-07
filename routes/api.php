<?php

use App\Helpers\JsonResponseHelper;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\PermissionController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::prefix('auth')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::middleware(JwtMiddleware::class)->group(function () {
            Route::post('logout', 'logout');
            Route::get('user', 'getUser');
        });
    });
});

Route::prefix('folders')->group(function () {
    Route::controller(FolderController::class)->middleware(JwtMiddleware::class)->group(function () {
        Route::get('root', 'foldersInRoot');
        Route::get('/{folder}', 'show')->missing(function () {
            return JsonResponseHelper::errorResponse('Folder Not Found', [], 404);
        });
        Route::post('', 'create');
        Route::put('/{folder}', 'update')->missing(function () {
            return JsonResponseHelper::errorResponse('Folder Not Found', [], 404);
        });
        Route::delete('/{folder}', 'delete')->missing(function () {
            return JsonResponseHelper::errorResponse('Folder Not Found', [], 404);
        });
        Route::get('download/{folder}', 'download')->missing(function () {
            return JsonResponseHelper::errorResponse('Folder Not Found', [], 404);
        });
    });
});

Route::prefix('files')->group(function () {
    Route::controller(FileController::class)->middleware(JwtMiddleware::class)->group(function () {
        Route::post('', 'create');
        Route::get('/{file}', 'show')->missing(function () {
            return JsonResponseHelper::errorResponse('File Not Found', [], 404);
        });
        Route::post('/{file}', 'update')->missing(function () {
            return JsonResponseHelper::errorResponse('File Not Found', [], 404);
        });
        Route::delete('/{file}', 'delete')->missing(function () {
            return JsonResponseHelper::errorResponse('File Not Found', [], 404);
        });
    });
});

Route::prefix('user-permissions')->group(function () {
    Route::controller(PermissionController::class)->middleware(JwtMiddleware::class)->group(function () {
        Route::post('folders/{folder}', 'create')->missing(function () {
            return JsonResponseHelper::errorResponse('folder not found', [], 404);
        });
        Route::put('{userPermission}', 'update')->missing(function () {
            return JsonResponseHelper::errorResponse('user permission not found', [], 404);
        });
        Route::delete('folders/{folder}/permissions/{permission}', 'delete')->missing(function () {
            return JsonResponseHelper::errorResponse('Folder or Permission not found', [], 404);
        });;
    });
});
