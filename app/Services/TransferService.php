<?php

namespace App\Services;

use App\DTOs\Transfer\TransferDTO;
use App\Enums\AccountStatus;
use App\Enums\TransferStatus;
use App\Enums\WalletStatus;
use App\Exceptions\BusinessException;
use App\Jobs\ProcessTransfer;
use App\Models\Transfer;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransferService
{
    /**
     * Create a new transfer and queue it for processing.
     */
    public function transfer(TransferDTO $dto): Transfer
    {
        // Check if transfer with same idempotency_key exists
        if ($dto->idempotency_key && Transfer::where('idempotency_key', $dto->idempotency_key)->exists()) {
            throw new BusinessException('Transferência já processada (idempotency key).');
        }
        
        // Generate idempotency key if not provided
        $idempotencyKey = $dto->idempotency_key ?? Str::uuid()->toString();
        
        $sourceWallet = Wallet::findOrFail($dto->source_wallet_id);
        $destinationWallet = Wallet::findOrFail($dto->destination_wallet_id);
        
        // Validate transfer
        $this->validateTransfer($sourceWallet, $destinationWallet, $dto->amount);
        
        // Create the transfer record
        $transfer = DB::transaction(function () use ($dto, $idempotencyKey) {
            return Transfer::create([
                'source_wallet_id' => $dto->source_wallet_id,
                'destination_wallet_id' => $dto->destination_wallet_id,
                'amount' => $dto->amount,
                'status' => TransferStatus::PENDING,
                'idempotency_key' => $idempotencyKey,
            ]);
        });
        
        // Queue the transfer for processing
        ProcessTransfer::dispatch($transfer);
        
        return $transfer;
    }

    /**
     * Validate if a transfer can be made.
     */
    private function validateTransfer(Wallet $sourceWallet, Wallet $destinationWallet, float $amount): void
    {
        // Check source wallet status
        if (!$sourceWallet->isActive()) {
            throw new BusinessException('A carteira de origem está inativa.');
        }
        
        // Check destination wallet status
        if (!$destinationWallet->isActive()) {
            throw new BusinessException('A carteira de destino está inativa.');
        }
        
        // Check source account status
        if (!$sourceWallet->account->isActive()) {
            throw new BusinessException('A conta de origem está bloqueada.');
        }
        
        // Check destination account status
        if (!$destinationWallet->account->isActive()) {
            throw new BusinessException('A conta de destino está bloqueada.');
        }
        
        // Check source wallet balance
        if ($sourceWallet->balance < $amount) {
            throw new BusinessException('Saldo insuficiente para realizar a transferência.');
        }
        
        // Check source user is approved
        $sourceUser = $sourceWallet->account->accountable;
        if (!$sourceUser->isApproved()) {
            throw new BusinessException('Usuário de origem não está aprovado.');
        }
        
        // Check amount is positive
        if ($amount <= 0) {
            throw new BusinessException('O valor da transferência deve ser maior que zero.');
        }
    }

    /**
     * Process a transfer (used in the job).
     */
    public function processTransfer(Transfer $transfer): Transfer
    {
        // Check if already processed
        if ($transfer->status !== TransferStatus::PENDING) {
            return $transfer;
        }
        
        return DB::transaction(function () use ($transfer) {
            $sourceWallet = $transfer->sourceWallet;
            $destinationWallet = $transfer->destinationWallet;
            
            try {
                // Re-validate at processing time
                $this->validateTransfer($sourceWallet, $destinationWallet, $transfer->amount);
                
                // Update balances
                $sourceWallet->updateBalance($sourceWallet->balance - $transfer->amount);
                $destinationWallet->updateBalance($destinationWallet->balance + $transfer->amount);
                
                // Mark transfer as completed
                $transfer->status = TransferStatus::COMPLETED;
                $transfer->save();
                
                return $transfer;
            } catch (BusinessException $e) {
                // Mark transfer as failed
                $transfer->status = TransferStatus::FAILED;
                $transfer->error_message = $e->getMessage();
                $transfer->save();
                
                throw $e;
            }
        });
    }

    /**
     * Find transfer by ID.
     */
    public function findById(int $id): ?Transfer
    {
        return Transfer::findOrFail($id);
    }
}
