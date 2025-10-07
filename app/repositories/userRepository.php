<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{

    public function findByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    public function createUserAccount(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }
}
