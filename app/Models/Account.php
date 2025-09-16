<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'status',
    ];

    /**
     * Get the accountable model (user or company).
     */
    public function accountable()
    {
        return $this->morphTo();
    }

    /**
     * Get the wallets associated with the account.
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Check if the account is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get the total balance of the account.
     */
    public function getTotalBalanceAttribute()
    {
        return $this->wallets->sum('balance');
    }
}