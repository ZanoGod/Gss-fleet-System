<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $input['role_id'] = null;
        $input['is_active'] = false;
        $user = User::create($input);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Your account is pending administrator approval.',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $input = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);
        $identifier = trim($input['login']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $guard = Auth::guard('web');
        $candidate = $guard->getProvider()->retrieveByCredentials([$field => $identifier]);
        if (! $candidate || ! Hash::check($input['password'], $candidate->getAuthPassword())) {
            throw ValidationException::withMessages([
                'login' => ['Incorrect username or password.'],
            ]);
        }

        if (! $candidate->is_active || $candidate->role_id === null) {
            throw ValidationException::withMessages([
                'login' => ['Your account is pending administrator approval. Contact an administrator.'],
            ]);
        }

        $guard->login($candidate);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function user(Request $request)
    {
        abort_unless($request->user()->is_active && $request->user()->role_id !== null, 403, 'Your account is pending administrator approval. Contact an administrator.');

        return response()->json(['success' => true, 'user' => $this->userPayload($request->user())])->header('Cache-Control', 'no-store');
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('role');
        return [
            ...$user->only(['id', 'name', 'username', 'email', 'is_active', 'role_id']),
            'role' => $user->role?->only(['id', 'name', 'slug', 'is_system']),
            'permissions' => $user->role?->permissions ?? [],
        ];
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => 'Logout successful']);
    }
}
