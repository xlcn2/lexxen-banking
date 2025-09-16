<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_wallet_id',
        'destination_wallet_id',
        'amount',
        'status',
        'idempotency_key',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the source wallet.
     */
    public function sourceWallet()
    {
        return $this->belongsTo(Wallet::class, 'source_wallet_id');
    }

    /**
     * Get the destination wallet.
     */
    public function destinationWallet()
    {
        return $this->belongsTo(Wallet::class, 'destination_wallet_id');
    }

    /**
     * Get the statements for this transfer.
     */
    public function statements()
    {
        return $this->hasMany(Statement::class);
    }

    /**
     * Check if transfer is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transfer is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transfer has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
    
}
