<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|max:255',
        ]);

        $validatedData['email'] = strtolower($validatedData['email']);
        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            $user = User::create($validatedData);
            $token = $user->createToken('authToken')->accessToken;

            $response = [
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $user,
                'token' => $token,
            ];

            return response()->json($response, 201);
        } catch (QueryException $e) {
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $loginData['email'] = strtolower($loginData['email']);

        try {
            if (!Auth::attempt($loginData)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
            }

            $token = Auth::user()->createToken('authToken')->accessToken;

            $response = [
                'status' => 'success',
                'message' => 'User logged in successfully',
                'user' => Auth::user(),
                'token' => $token,
            ];

            return response()->json($response, 200);
        } catch (QueryException $e) {
            return response()->json(['status' => 'error', 'message' => "Internal Server Error"], 500);
        }
    }
}
