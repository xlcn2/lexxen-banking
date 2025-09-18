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
use App\Models\Statement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        
        $sourceWallet = Wallet::with('account.accountable')->findOrFail($dto->source_wallet_id);
        $destinationWallet = Wallet::with('account')->findOrFail($dto->destination_wallet_id);
        
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
        // Usando o transfer criado, não um array de dados
        ProcessTransfer::dispatch($transfer);
        
        return $transfer;
    }

    /**
     * Validate if a transfer can be made.
     */
    private function validateTransfer(Wallet $sourceWallet, Wallet $destinationWallet, float $amount): void
    {
        // Check source wallet status
        if ($sourceWallet->status !== 'active') {
            throw new BusinessException('A carteira de origem está inativa.');
        }
        
        // Check destination wallet status
        if ($destinationWallet->status !== 'active') {
            throw new BusinessException('A carteira de destino está inativa.');
        }
        
        // Check source account status
        if ($sourceWallet->account->status === 'blocked') {
            throw new BusinessException('A conta de origem está bloqueada.');
        }
        
        // Check destination account status
        if ($destinationWallet->account->status === 'blocked') {
            throw new BusinessException('A conta de destino está bloqueada.');
        }
        
        // Check source wallet balance
        if ($sourceWallet->balance < $amount) {
            throw new BusinessException('Saldo insuficiente para realizar a transferência.');
        }
        
        // Check source user is approved (se existir essa regra)
        if (method_exists($sourceWallet->account->accountable, 'isApproved') && 
            !$sourceWallet->account->accountable->isApproved()) {
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
        Log::info('Processando transferência', ['transfer_id' => $transfer->id]);
        
        // Check if already processed
        if ($transfer->status !== 'pending') {
            Log::info('Transferência já processada', [
                'transfer_id' => $transfer->id, 
                'status' => $transfer->status
            ]);
            return $transfer;
        }
        
        return DB::transaction(function () use ($transfer) {
            // Usar lockForUpdate para evitar concorrência
            $sourceWallet = Wallet::with('account')->lockForUpdate()->findOrFail($transfer->source_wallet_id);
            $destinationWallet = Wallet::with('account')->lockForUpdate()->findOrFail($transfer->destination_wallet_id);
            
            try {
                // Re-validate at processing time
                $this->validateTransfer($sourceWallet, $destinationWallet, $transfer->amount);
                
                // Determinar se é transferência externa (entre contas diferentes)
                $isExternalTransfer = $sourceWallet->account_id !== $destinationWallet->account_id;
                
                // Se for transferência externa, verificar se o destino é carteira DEFAULT
                if ($isExternalTransfer && $destinationWallet->type !== 'default') {
                    // Buscar a carteira DEFAULT do destinatário
                    $defaultWallet = Wallet::where('account_id', $destinationWallet->account_id)
                        ->where('type', 'default')
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->first();
                        
                    if (!$defaultWallet) {
                        throw new BusinessException('Não foi possível encontrar a carteira principal do destinatário');
                    }
                    
                    // Atualizar a carteira de destino para a DEFAULT
                    $destinationWallet = $defaultWallet;
                    $transfer->destination_wallet_id = $defaultWallet->id;
                    $transfer->save();
                    
                    Log::info('Transferência redirecionada para carteira DEFAULT', [
                        'original_wallet_id' => $transfer->destination_wallet_id,
                        'default_wallet_id' => $defaultWallet->id
                    ]);
                }
                
                // Atualizar os saldos
                $sourceWallet->balance -= $transfer->amount;
                $destinationWallet->balance += $transfer->amount;
                
                $sourceWallet->save();
                $destinationWallet->save();
                
                // Marcar transferência como completa
                $transfer->status = 'completed';
                $transfer->save();
                
                // Criar statements
                Statement::create([
                    'wallet_id' => $sourceWallet->id,
                    'transfer_id' => $transfer->id,
                    'type' => 'debit',
                    'amount' => $transfer->amount,
                    'balance_after' => $sourceWallet->balance,
                ]);

                Statement::create([
                    'wallet_id' => $destinationWallet->id,
                    'transfer_id' => $transfer->id,
                    'type' => 'credit',
                    'amount' => $transfer->amount,
                    'balance_after' => $destinationWallet->balance,
                ]);
                
                Log::info('Transferência processada com sucesso', [
                    'transfer_id' => $transfer->id,
                    'source' => $sourceWallet->id,
                    'destination' => $destinationWallet->id,
                    'amount' => $transfer->amount
                ]);
                
                return $transfer;
            } catch (BusinessException $e) {
                // Mark transfer as failed
                $transfer->status = 'failed';
                $transfer->error_message = $e->getMessage();
                $transfer->save();
                
                Log::error('Erro ao processar transferência', [
                    'transfer_id' => $transfer->id,
                    'error' => $e->getMessage()
                ]);
                
                throw $e;
            }
        }, 5); // 5 tentativas em caso de deadlock
    }

    /**
     * Find transfer by ID.
     */
    public function findById(int $id): ?Transfer
    {
        return Transfer::findOrFail($id);
    }
}