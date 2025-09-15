<?php

namespace App\Services;

use App\DTOs\User\CorporateUserDTO;
use App\DTOs\User\UserStatusUpdateDTO;
use App\Enums\UserStatus;
use App\Enums\WalletType;
use App\Models\CorporateUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CorporateUserService
{
    /**
     * Create a new corporate user.
     */
    public function create(CorporateUserDTO $dto): CorporateUser
    {
        $data = $dto->toArray();
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        return CorporateUser::create($data);
    }

    /**
     * Get corporate user by ID.
     */
    public function findById(int $id): ?CorporateUser
    {
        return CorporateUser::findOrFail($id);
    }

    /**
     * Update corporate user.
     */
    public function update(CorporateUser $user, CorporateUserDTO $dto): CorporateUser
    {
        $data = $dto->toArray();
        
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        $user->update($data);
        
        return $user;
    }

    /**
     * Delete corporate user (soft delete).
     */
    public function delete(CorporateUser $user): bool
    {
        return $user->delete();
    }

    /**
     * Update user status.
     */
    public function updateStatus(CorporateUser $user, UserStatusUpdateDTO $dto): CorporateUser
    {
        $user->status = $dto->status;
        $user->save();
        
        // If user is approved, create default wallet
        if ($dto->status === UserStatus::APPROVED) {
            $this->createDefaultWalletIfNeeded($user);
        }
        
        return $user;
    }

    /**
     * Create default wallet for user if needed.
     */
    private function createDefaultWalletIfNeeded(CorporateUser $user): void
    {
        DB::transaction(function () use ($user) {
            foreach ($user->accounts as $account) {
                $defaultWallet = $account->wallets()->where('type', WalletType::DEFAULT)->first();
                
                if (!$defaultWallet) {
                    $account->wallets()->create([
                        'name' => 'Carteira Padrão',
                        'balance' => 0,
                        'type' => WalletType::DEFAULT,
                        'status' => 'active',
                    ]);
                }
            }
        });
    }

    /**
     * Get users pending approval.
     */
    public function getPendingApproval(): Collection
    {
        return CorporateUser::where('status', UserStatus::PENDING_APPROVAL)->get();
    }

    /**
     * Process users approval in batch.
     */
    public function processApprovalBatch(array $userIds, UserStatus $status): int
    {
        return DB::transaction(function () use ($userIds, $status) {
            $count = CorporateUser::whereIn('id', $userIds)
                ->update(['status' => $status]);
            
            if ($status === UserStatus::APPROVED) {
                $users = CorporateUser::whereIn('id', $userIds)->get();
                
                foreach ($users as $user) {
                    $this->createDefaultWalletIfNeeded($user);
                }
            }
            
            return $count;
        });
    }
}
