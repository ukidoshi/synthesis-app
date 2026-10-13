<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chemist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChemistAuthController extends Controller
{
    // 8. POST Регистрация
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:chemists,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        Chemist::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return response()->json([], 201);
    }

    // 9. POST Аутентификация
    public function login(Request $request)
    {
        return response()->json(['message' => 'User logged in (Stub)']);
    }

    // 10. POST Деавторизация
    public function logout(Request $request)
    {
        return response()->json(['message' => 'User logged out (Stub)']);
    }
}
