<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController as WalletController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('wallets', WalletController::class);
    Route::post('/wallets/{id}/deposit', [TransactionController::class, 'deposit']);
    Route::post('/wallets/{id}/withdraw', [TransactionController::class, 'withdraw']);
    Route::post('/wallets/{id}/transfer', [TransactionController::class, 'transfer']);
    Route::get('/wallets/{id}/transactions', [TransactionController::class, 'index']);
});