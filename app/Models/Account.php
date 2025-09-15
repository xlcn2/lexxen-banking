<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'status',
    ];

    /**
     * Get the owning accountable model (individual or corporate user).
     */
    public function accountable()
    {
        return $this->morphTo();
    }

    /**
     * Get the wallets for this account.
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the total balance of all wallets in this account.
     */
    public function getTotalBalanceAttribute()
    {
        return $this->wallets()->sum('balance');
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the default wallet for this account.
     */
    public function defaultWallet()
    {
        return $this->wallets()->where('type', 'default')->first();
    }
}
