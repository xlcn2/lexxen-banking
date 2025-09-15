<?php

namespace App\Services;

use App\DTOs\Account\AccountDTO;
use App\DTOs\Account\AccountStatusUpdateDTO;
use App\Models\Account;
use App\Models\CorporateUser;
use App\Models\IndividualUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountService
{
    /**
     * Create a new account for individual user.
     */
    public function createForIndividual(IndividualUser $user, AccountDTO $dto): Account
    {
        return DB::transaction(function () use ($user, $dto) {
            $account = $user->accounts()->create([
                'number' => $dto->number ?? $this->generateAccountNumber(),
                'status' => $dto->status,
            ]);
            
            // If user is approved, create default wallet
            if ($user->isApproved()) {
                $account->wallets()->create([
                    'name' => 'Carteira Padrão',
                    'balance' => 0,
                    'type' => 'default',
                    'status' => 'active',
                ]);
            }
            
            return $account;
        });
    }

    /**
     * Create a new account for corporate user.
     */
    public function createForCorporate(CorporateUser $user, AccountDTO $dto): Account
    {
        return DB::transaction(function () use ($user, $dto) {
            $account = $user->accounts()->create([
                'number' => $dto->number ?? $this->generateAccountNumber(),
                'status' => $dto->status,
            ]);
            
            // If user is approved, create default wallet
            if ($user->isApproved()) {
                $account->wallets()->create([
                    'name' => 'Carteira Padrão',
                    'balance' => 0,
                    'type' => 'default',
                    'status' => 'active',
                ]);
            }
            
            return $account;
        });
    }

    /**
     * Get account by ID.
     */
    public function findById(int $id): ?Account
    {
        return Account::findOrFail($id);
    }

    /**
     * Update account status.
     */
    public function updateStatus(Account $account, AccountStatusUpdateDTO $dto): Account
    {
        $account->status = $dto->status;
        $account->save();
        
        return $account;
    }

    /**
     * Delete account (soft delete).
     */
    public function delete(Account $account): bool
    {
        return $account->delete();
    }

    /**
     * Generate unique account number.
     */
    private function generateAccountNumber(): string
    {
        do {
            $number = 'A' . Str::padLeft(random_int(1, 99999999), 8, '0');
        } while (Account::where('number', $number)->exists());

        return $number;
    }
}
