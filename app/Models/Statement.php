<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'transfer_id',
        'type',
        'amount',
        'balance_after',
        'description',
    ];

    /**
     * Get the wallet that owns the statement.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the transfer associated with the statement.
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}