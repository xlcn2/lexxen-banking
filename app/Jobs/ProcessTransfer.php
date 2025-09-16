<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Models\Statement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transferData;

    public function __construct(array $transferData)
    {
        $this->transferData = $transferData;
    }

    public function handle(): void
    {
        Log::info('Iniciando processamento de transferência', $this->transferData);

        // Verificar idempotência
        if (isset($this->transferData['idempotency_key'])) {
            $existingTransfer = Transfer::where('idempotency_key', $this->transferData['idempotency_key'])
                ->where('status', 'completed')
                ->first();

            if ($existingTransfer) {
                Log::info('Transferência já processada anteriormente. Ignorando.', [
                    'transfer_id' => $existingTransfer->id,
                    'idempotency_key' => $this->transferData['idempotency_key']
                ]);
                return;
            }
        }

        // Criar ou recuperar a transferência
        $transfer = isset($this->transferData['id']) 
            ? Transfer::find($this->transferData['id']) 
            : new Transfer();

        if (isset($this->transferData['id']) && !$transfer) {
            Log::error('Transferência não encontrada', ['id' => $this->transferData['id']]);
            return;
        }

        // Preencher dados da transferência
        if (!isset($this->transferData['id'])) {
            $transfer->source_wallet_id = $this->transferData['source_wallet_id'];
            $transfer->destination_wallet_id = $this->transferData['destination_wallet_id'];
            $transfer->amount = $this->transferData['amount'];
            $transfer->status = 'pending';
            $transfer->idempotency_key = $this->transferData['idempotency_key'] ?? null;
            $transfer->save();
        }

        // Processar a transferência dentro de uma transação
        try {
            DB::transaction(function () use ($transfer) {
                // Bloqueio das carteiras para evitar race conditions
                $sourceWallet = Wallet::with('account')->lockForUpdate()->find($transfer->source_wallet_id);
                $destinationWallet = Wallet::with('account')->lockForUpdate()->find($transfer->destination_wallet_id);

                // Verificações
                if (!$sourceWallet || !$destinationWallet) {
                    throw new \Exception('Carteira não encontrada');
                }

                // Verificar se conta origem está bloqueada
                if ($sourceWallet->account->status === 'blocked') {
                    throw new \Exception('A conta de origem está bloqueada');
                }

                // Verificar se as carteiras estão ativas
                if ($sourceWallet->status !== 'active' || $destinationWallet->status !== 'active') {
                    throw new \Exception('Uma das carteiras não está ativa');
                }

                // Verificar saldo suficiente
                if ($sourceWallet->balance < $transfer->amount) {
                    throw new \Exception('Saldo insuficiente para realizar a transferência');
                }
                
                // Determinar se é uma transferência para conta de terceiro
                $isExternalTransfer = $sourceWallet->account_id !== $destinationWallet->account_id;
                
                // Se for transferência externa, verificar e redirecionar para carteira DEFAULT
                if ($isExternalTransfer) {
                    // Verificar se a carteira de destino é do tipo DEFAULT
                    if ($destinationWallet->type !== 'default') {
                        // Buscar a carteira DEFAULT do destinatário
                        $defaultWallet = Wallet::where('account_id', $destinationWallet->account_id)
                            ->where('type', 'default')
                            ->where('status', 'active')
                            ->lockForUpdate()
                            ->first();
                            
                        if (!$defaultWallet) {
                            throw new \Exception('Não foi possível encontrar a carteira principal do destinatário');
                        }
                        
                        // Atualizar a carteira de destino para a DEFAULT
                        $destinationWallet = $defaultWallet;
                        $transfer->destination_wallet_id = $defaultWallet->id;
                        $transfer->save();
                        
                        Log::info('Transferência redirecionada para carteira DEFAULT', [
                            'original_wallet_id' => $this->transferData['destination_wallet_id'],
                            'default_wallet_id' => $defaultWallet->id
                        ]);
                    }
                }

                // Realizar a transferência
                $sourceWallet->balance -= $transfer->amount;
                $destinationWallet->balance += $transfer->amount;

                $sourceWallet->save();
                $destinationWallet->save();

                // Atualizar status da transferência
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
            }, 5); // 5 tentativas em caso de deadlock
        } catch (\Exception $e) {
            Log::error('Erro ao processar transferência', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage()
            ]);

            // Atualizar status da transferência para falha
            $transfer->status = 'failed';
            $transfer->error_message = $e->getMessage();
            $transfer->save();

            // Relançar exceção para que o job possa ser retentado
            throw $e;
        }
    }
}