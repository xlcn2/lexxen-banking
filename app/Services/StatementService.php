<?php

namespace App\Services;

use App\DTOs\Transfer\StatementFilterDTO;
use App\Enums\StatementType;
use App\Models\Statement;
use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class StatementService
{
    /**
     * Get statements for a wallet with filters.
     */
    public function getStatements(Wallet $wallet, StatementFilterDTO $dto): LengthAwarePaginator
    {
        $query = $wallet->statements()->with('transfer');
        
        // Apply date filters if provided
        if ($dto->start_date) {
            $query->where('created_at', '>=', $dto->start_date);
        }
        
        if ($dto->end_date) {
            $query->where('created_at', '<=', $dto->end_date);
        }
        
        // Order by date descending
        $query->orderBy('created_at', 'desc');
        
        return $query->paginate($dto->per_page, ['*'], 'page', $dto->page);
    }

    /**
     * Create a statement record for a transfer.
     */
    public function createStatementForTransfer(
        int $wallet_id,
        int $transfer_id,
        StatementType $type,
        float $amount,
        float $balance_after
    ): Statement {
        $statement = Statement::create([
            'wallet_id' => $wallet_id,
            'transfer_id' => $transfer_id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balance_after,
        ]);
        
        // Clear cache for this wallet
        Cache::forget("wallet_{$wallet_id}_balance");
        
        return $statement;
    }
}
