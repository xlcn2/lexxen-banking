<?php

namespace App\Observers;

use App\Models\Transfer;
use App\Models\Statement;

class TransferObserver
{
    /**
     * Handle the Transfer "created" event.
     */
    public function created(Transfer $transfer)
    {
        // As transferências são processadas assincronamente,
        // não precisamos criar statements no evento created
    }

    /**
     * Handle the Transfer "updated" event.
     */
    public function updated(Transfer $transfer)
    {
        // Verificar se a transferência foi concluída
        if ($transfer->status === 'completed' && $transfer->getOriginal('status') !== 'completed') {
            $this->createStatements($transfer);
        }
    }

    /**
     * Handle the Transfer "deleted" event.
     */
    public function deleted(Transfer $transfer)
    {
        // Normalmente transferências não seriam excluídas em um sistema financeiro
    }

    /**
     * Create statements for completed transfer.
     */
    private function createStatements(Transfer $transfer)
    {
        // Verificar se os statements já existem para evitar duplicação
        $existingStatements = Statement::where('transfer_id', $transfer->id)->count();
        
        if ($existingStatements > 0) {
            return; // Evitar duplicação
        }

        // Carregar as carteiras para ter acesso aos saldos atualizados
        $transfer->sourceWallet->refresh();
        $transfer->destinationWallet->refresh();

        // Criar statement para a carteira de origem (débito)
        Statement::create([
            'wallet_id' => $transfer->source_wallet_id,
            'transfer_id' => $transfer->id,
            'type' => 'debit',
            'amount' => -$transfer->amount, // Valor negativo para débito
            'balance_after' => $transfer->sourceWallet->balance,
            'description' => 'Transferência enviada',
        ]);

        // Criar statement para a carteira de destino (crédito)
        Statement::create([
            'wallet_id' => $transfer->destination_wallet_id,
            'transfer_id' => $transfer->id,
            'type' => 'credit',
            'amount' => $transfer->amount, // Valor positivo para crédito
            'balance_after' => $transfer->destinationWallet->balance,
            'description' => 'Transferência recebida',
        ]);
    }
}