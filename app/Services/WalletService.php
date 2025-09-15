<?php

namespace App\Services;

use App\DTOs\Wallet\WalletDTO;
use App\DTOs\Wallet\WalletStatusUpdateDTO;
use App\Enums\WalletStatus;
use App\Enums\WalletType;
use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BusinessException;

class WalletService
{
    /**
     * Create a new wallet.
     */
    public function create(Account $account, WalletDTO $dto): Wallet
    {
        if ($dto->type === WalletType::DEFAULT) {
            throw new BusinessException('Não é possível criar uma carteira do tipo DEFAULT manualmente.');
        }
        
        return DB::transaction(function () use ($account, $dto) {
            return $account->wallets()->create([
                'name' => $dto->name,
                'balance' => $dto->balance,
                'type' => $dto->type,
                'status' => $dto->status,
            ]);
        });
    }

    /**
     * Get wallet by ID.
     */
    public function findById(int $id): ?Wallet
    {
        return Wallet::findOrFail($id);
    }

    /**
     * Update wallet status.
     */
    public function updateStatus(Wallet $wallet, WalletStatusUpdateDTO $dto): Wallet
    {
        return DB::transaction(function () use ($wallet, $dto) {
            // Check if wallet has balance when trying to deactivate
            if ($dto->status === WalletStatus::INACTIVE && $wallet->balance > 0) {
                throw new BusinessException('Não é possível desativar uma carteira com saldo. Transfira o saldo primeiro.');
            }
            
            $wallet->status = $dto->status;
            $wallet->save();
            
            return $wallet;
        });
    }

    /**
     * Delete wallet (soft delete).
     */
    public function delete(Wallet $wallet): bool
    {
        if ($wallet->balance > 0) {
            throw new BusinessException('Não é possível excluir uma carteira com saldo. Transfira o saldo primeiro.');
        }
        
        return $wallet->delete();
    }
}
