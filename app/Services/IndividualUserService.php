<?php

namespace App\Services;

use App\DTOs\User\IndividualUserDTO;
use App\DTOs\User\UserStatusUpdateDTO;
use App\Enums\UserStatus;
use App\Enums\WalletType;
use App\Models\IndividualUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IndividualUserService
{
    /**
     * Create a new individual user.
     */
    public function create(IndividualUserDTO $dto): IndividualUser
    {
        $data = $dto->toArray();
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        return IndividualUser::create($data);
    }

    /**
     * Get individual user by ID.
     */
    public function findById(int $id): ?IndividualUser
    {
        return IndividualUser::findOrFail($id);
    }

    /**
     * Update individual user.
     */
    public function update(IndividualUser $user, IndividualUserDTO $dto): IndividualUser
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
     * Delete individual user (soft delete).
     */
    public function delete(IndividualUser $user): bool
    {
        return $user->delete();
    }

    /**
     * Update user status.
     */
    public function updateStatus(IndividualUser $user, UserStatusUpdateDTO $dto): IndividualUser
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
    private function createDefaultWalletIfNeeded(IndividualUser $user): void
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
        return IndividualUser::where('status', UserStatus::PENDING_APPROVAL)->get();
    }

    /**
     * Process users approval in batch.
     */
    public function processApprovalBatch(array $userIds, UserStatus $status): int
    {
        return DB::transaction(function () use ($userIds, $status) {
            $count = IndividualUser::whereIn('id', $userIds)
                ->update(['status' => $status]);
            
            if ($status === UserStatus::APPROVED) {
                $users = IndividualUser::whereIn('id', $userIds)->get();
                
                foreach ($users as $user) {
                    $this->createDefaultWalletIfNeeded($user);
                }
            }
            
            return $count;
        });
    }
}
