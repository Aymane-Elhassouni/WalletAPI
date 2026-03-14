<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $wallets = $request->user()->wallets;

        return response()->json([
            "success" => true,
            "message" => "Liste des wallets récupérée.",
            "data" => [
                "wallets" => $wallets
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validate = $request->validate([
                'name' => 'required|string|max:255',
                'currency' => 'required|in:MAD,EUR,USD',
            ], [
                'name.required' => "Le nom du wallet est obligatoire.",
                'currency.required' => "La devise est obligatoire.",
                'currency.in' => "La devise sélectionnée n'est pas valide.",
            ]);

            $wallet = Wallet::create([
                'user_id' => $request->user()->id,
                'name' => $validate['name'],
                'currency' => $validate['currency'],
                'balance' => 0.00,
            ]);
            return response()->json([
                "success" => true,
                "message" => "Wallet créé avec succès.",
                "data" => [
                    "wallet" => $wallet
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

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request): JsonResponse
    {
        $wallet = Wallet::find($id);

        if (!$wallet) {
            return response()->json([
                "success" => false,
                "message" => "Wallet introuvable."
            ], 404);
        }

        if ($wallet->user_id !== $request->user()->id) {
            return response()->json([
                "success" => false,
                "message" => "Vous n'êtes pas autorisé à accéder à ce wallet."
            ], 403);
        }

        return response()->json([
            "success" => true,
            "message" => "Détail du wallet récupéré.",
            "data" => [
                "wallet" => $wallet
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wallet $wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wallet $wallet)
    {
        //
    }
}
