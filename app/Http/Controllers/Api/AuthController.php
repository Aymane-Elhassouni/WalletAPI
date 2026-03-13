<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validate = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'email.unique' => "L'adresse email est déjà utilisée.",
                'password.min' => "Le mot de passe doit contenir au moins 8 caractères.",
            ]);

            $user = User::create([
                'name' => $validate['name'],
                'email' => $validate['email'],
                'password' => Hash::make($validate['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                "success" => true,
                "message" => "Inscription réussie.",
                "data" => [
                    "user" => $user,
                    "token" => $token
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "success" => false,
                "message" => "Erreur de validation.",
                "errors" => $e->errors()
            ], 422);
        }
    }
    public function login(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $validate['email'])->first();

        if (!$user || !Hash::check($validate['password'], $user->password)) {
            return response()->json([
                "success" => false,
                "message" => "Identifiants incorrects."
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            "success" => true,
            "message" => "Connexion réussie.",
            "data" => [
                "user" => $user,
                "token" => $token,
                "token_type" => "Bearer"
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        if (!auth('sanctum')->check()) {
            return response()->json([
                "success" => false,
                "message" => "Non authentifié."
            ], 401);
        }

        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            "success" => true,
            "message" => "Déconnexion réussie."
        ], 200);
    }
}
