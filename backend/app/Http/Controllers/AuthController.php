<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
        $user = \App\Models\User::create($input);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        return response()->json(['success' => true, 'user' => $user->only(['id', 'name', 'username', 'email'])], 201);
    }

    public function login(Request $request)
    {
        $input = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $identifier = trim($input['login']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::guard('web')->attempt([$field => $identifier, 'password' => $input['password']])) {
            throw ValidationException::withMessages([
                'login' => ['Incorrect username or password.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $request->user()->only(['id', 'name', 'username', 'email']),
        ]);
    }

    public function user(Request $request)
    {
        return response()->json(['success' => true, 'user' => $request->user()->only(['id', 'name', 'username', 'email'])])->header('Cache-Control', 'no-store');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => 'Logout successful']);
    }
}
