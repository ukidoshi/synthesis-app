<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AuthSingleton
{
    public static function getId(): ?int
    {
        // Текущий химик из JWT (guard api); null — гость без токена
        return Auth::guard('api')->id();
    }
}
