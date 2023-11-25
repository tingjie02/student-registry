<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        ]);

        $validatedData['email'] = strtolower($validatedData['email']);
        $validatedData['password'] = Hash::make($validatedData['password']);

        $user = User::create($validatedData);

        $token = $user->createToken('authToken')->accessToken;

        $response = [
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
        ];

        return response()->json($response, 201);
    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $loginData['email'] = strtolower($loginData['email']);

        if (!Auth::attempt($loginData)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = Auth::user()->createToken('authToken')->accessToken;

        $response = [
            'message' => 'User logged in successfully',
            'user' => Auth::user(),
            'token' => $token,
        ];

        return response()->json($response, 200);
    }
}
