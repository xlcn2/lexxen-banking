<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'balance',
        'type',
        'status',
        'account_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
    ];

    /**
     * Get the account that owns the wallet.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the statements for this wallet.
     */
    public function statements()
    {
        return $this->hasMany(Statement::class);
    }

    /**
     * Get outgoing transfers from this wallet.
     */
    public function outgoingTransfers()
    {
        return $this->hasMany(Transfer::class, 'source_wallet_id');
    }

    /**
     * Get incoming transfers to this wallet.
     */
    public function incomingTransfers()
    {
        return $this->hasMany(Transfer::class, 'destination_wallet_id');
    }

    /**
     * Check if wallet is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if wallet is default type
     */
    public function isDefault(): bool
    {
        return $this->type === 'default';
    }

    
}
