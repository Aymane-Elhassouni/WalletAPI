<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', function (Request $request) {
    $user = User::where('email', $request->email)->firstOrFail();
    $token = $user->createToken('auth_token')->plainTextToken;
    return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
});

Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();

    return response()->json([
        'message' => 'Déconnexion réussie (jeton supprimé)'
    ]);
})->middleware('auth:sanctum');

Route::get('/profile', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
