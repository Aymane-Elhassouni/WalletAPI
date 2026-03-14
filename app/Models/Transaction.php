<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'description',
        'balance_after',
        'sender_wallet_id',
        'receiver_wallet_id'
    ];
    public function sender()
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }
    public function receiver()
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id');
    }
}
