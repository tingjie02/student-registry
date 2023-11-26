<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        // Validate input data with rules
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|max:255',
        ]);

        // Process input data
        $validatedData['email'] = strtolower($validatedData['email']);
        $validatedData['password'] = Hash::make($validatedData['password']);

        try {
            // Create user and generate token
            $user = User::create($validatedData);
            $token = $user->createToken('authToken')->accessToken;

            // Prepare success response
            $response = [
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => $user,
                'token' => $token,
            ];

            return response()->json($response, 201);
        } catch (QueryException $e) {
            // Handle query exception
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        // Validate input data with rules
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Process login data
        $loginData['email'] = strtolower($loginData['email']);

        try {
            // Attempt to authenticate user
            if (!Auth::attempt($loginData)) {
                return response()->json(['status' => 'error', 'message' => 'Invalid credentials'], 401);
            }

            // Generate token upon successful authentication
            $token = Auth::user()->createToken('authToken')->accessToken;

            // Prepare success respons
            $response = [
                'status' => 'success',
                'message' => 'User logged in successfully',
                'user' => Auth::user(),
                'token' => $token,
            ];

            return response()->json($response, 200);
        } catch (QueryException $e) {
            // Handle query exception
            return response()->json(['status' => 'error', 'message' => "Internal Server Error"], 500);
        }
    }
}
