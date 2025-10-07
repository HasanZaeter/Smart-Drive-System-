<?php

namespace App\Services;

use App\Helpers\JsonResponseHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\AuthRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    protected $userRepo;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepo = $userRepository;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->userRepo->findByEmail($request->email);

        if ($user) {
            return JsonResponseHelper::errorResponse('The Email has already been taken', [], 400);
        }

        $user = $this->userRepo->createUserAccount($request->validated());

        return JsonResponseHelper::successResponse('register successfully', $user, 201);

    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $user = $this->userRepo->findByEmail($credentials['email']);
        if (! $user) {
            return JsonResponseHelper::errorResponse([], 'Email not found. Please register or check your email', 404);
        }

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return JsonResponseHelper::errorResponse([], 'Invalid password. Please check your password', 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // $user = $this->userRepo->findByEmail($request->email);

        $data = [
            'token' => $token,
            'token_type' => 'bearer',
            'user' => new UserResource($user),
        ];

        // return $data;

        return JsonResponseHelper::successResponse($data, 'Login successful');
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return JsonResponseHelper::successResponse('', 'logout successful');
    }
}
