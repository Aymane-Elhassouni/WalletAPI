<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function index(Request $request, $id): JsonResponse
    {
        $wallet = Wallet::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$wallet) {
            return response()->json([
                "success" => false,
                "message" => "Wallet introuvable."
            ], 404);
        }

        $perPage = $request->query('per_page', 15);

        $transactions = Transaction::where('wallet_id', $id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            "success" => true,
            "message" => "Historique des transactions récupéré.",
            "data" => [
                "transactions" => $transactions->items(),
                "pagination" => [
                    "current_page" => $transactions->currentPage(),
                    "last_page" => $transactions->lastPage(),
                    "per_page" => $transactions->perPage(),
                    "total" => $transactions->total(),
                ]
            ]
        ], 200);
    }
    public function deposit(Request $request, $id): JsonResponse
    {
        try {
            $validate = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255'
            ], [
                'amount.min' => "Le montant doit être supérieur à 0.",
                'amount.required' => "Le montant est obligatoire."
            ]);

            $wallet = Wallet::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$wallet) {
                return response()->json(["success" => false, "message" => "Wallet introuvable."], 404);
            }

            $transaction = DB::transaction(function () use ($wallet, $validate) {
                $wallet->balance += $validate['amount'];
                $wallet->save();

                return Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'deposit',
                    'amount' => $validate['amount'],
                    'description' => $validate['description'],
                    'balance_after' => $wallet->balance
                ]);
            });

            return response()->json([
                "success" => true,
                "message" => "Dépôt effectué avec succès.",
                "data" => [
                    "transaction" => $transaction,
                    "wallet" => [
                        "id" => $wallet->id,
                        "name" => $wallet->name,
                        "currency" => $wallet->currency,
                        "balance" => number_format($wallet->balance, 2, '.', '')
                    ]
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "success" => false,
                "message" => "Erreur de validation.",
                "errors" => $e->errors()
            ], 422);
        }
    }
    public function withdraw(Request $request, $id): JsonResponse
    {
        try {
            $validate = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255'
            ], [
                'amount.min' => "Le montant doit être supérieur à 0.",
                'amount.required' => "Le montant est obligatoire."
            ]);

            $wallet = Wallet::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$wallet) {
                return response()->json(["success" => false, "message" => "Wallet introuvable."], 404);
            }

            if ($wallet->balance < $validate['amount']) {
                return response()->json([
                    "success" => false,
                    "message" => "Solde insuffisant. Solde actuel : " . number_format($wallet->balance, 2, '.', '') . " " . $wallet->currency . "."
                ], 400);
            }

            $transaction = DB::transaction(function () use ($wallet, $validate) {
                $wallet->balance -= $validate['amount'];
                $wallet->save();

                return Transaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => 'withdraw',
                    'amount' => $validate['amount'],
                    'description' => $validate['description'],
                    'balance_after' => $wallet->balance
                ]);
            });

            return response()->json([
                "success" => true,
                "message" => "Retrait effectué avec succès.",
                "data" => [
                    "transaction" => $transaction,
                    "wallet" => [
                        "id" => $wallet->id,
                        "name" => $wallet->name,
                        "currency" => $wallet->currency,
                        "balance" => number_format($wallet->balance, 2, '.', '')
                    ]
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "success" => false,
                "message" => "Erreur de validation.",
                "errors" => $e->errors()
            ], 422);
        }
    }
    public function transfer(Request $request, $id): JsonResponse
    {
        try {
            $fields = $request->validate([
                'receiver_wallet_id' => 'required|exists:wallets,id',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string|max:255'
            ], [
                'receiver_wallet_id.required' => "Le wallet destinataire est obligatoire.",
                'receiver_wallet_id.exists' => "Le wallet destinataire est introuvable.",
                'amount.min' => "Le montant doit être supérieur à 0."
            ]);

            $senderWallet = Wallet::where('id', $id)
                ->where('user_id', $request->user()->id)
                ->first();

            $receiverWallet = Wallet::find($fields['receiver_wallet_id']);

            if (!$senderWallet) {
                return response()->json(["success" => false, "message" => "Wallet source introuvable."], 404);
            }

            if ($senderWallet->currency !== $receiverWallet->currency) {
                return response()->json([
                    "success" => false,
                    "message" => "Transfert impossible : les deux wallets doivent avoir la même devise."
                ], 400);
            }

            if ($senderWallet->balance < $fields['amount']) {
                return response()->json([
                    "success" => false,
                    "message" => "Solde insuffisant. Solde actuel : " . number_format($senderWallet->balance, 2, '.', '') . " " . $senderWallet->currency . "."
                ], 400);
            }

            $result = DB::transaction(function () use ($senderWallet, $receiverWallet, $fields) {
                $senderWallet->balance -= $fields['amount'];
                $senderWallet->save();

                $receiverWallet->balance += $fields['amount'];
                $receiverWallet->save();

                $tOut = Transaction::create([
                    'wallet_id' => $senderWallet->id,
                    'type' => 'transfer_out',
                    'amount' => $fields['amount'],
                    'description' => $fields['description'],
                    'receiver_wallet_id' => $receiverWallet->id,
                    'balance_after' => $senderWallet->balance
                ]);

                $tIn = Transaction::create([
                    'wallet_id' => $receiverWallet->id,
                    'type' => 'transfer_in',
                    'amount' => $fields['amount'],
                    'description' => $fields['description'],
                    'sender_wallet_id' => $senderWallet->id,
                    'balance_after' => $receiverWallet->balance
                ]);

                return ['out' => $tOut, 'in' => $tIn];
            });

            return response()->json([
                "success" => true,
                "message" => "Transfert effectué avec succès.",
                "data" => [
                    "transaction_out" => $result['out'],
                    "transaction_in" => $result['in'],
                    "wallet" => [
                        "id" => $senderWallet->id,
                        "name" => $senderWallet->name,
                        "currency" => $senderWallet->currency,
                        "balance" => number_format($senderWallet->balance, 2, '.', '')
                    ]
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "success" => false,
                "message" => "Erreur de validation.",
                "errors" => $e->errors()
            ], 422);
        }
    }
}
