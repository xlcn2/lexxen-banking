<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Transfer;
use App\Services\TransferService;
use Illuminate\Support\Facades\Log;

class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transfer;

    /**
     * Create a new job instance.
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Execute the job.
     */
    public function handle(TransferService $transferService): void
    {
        Log::info('Iniciando processamento de transferência', [
            'transfer_id' => $this->transfer->id,
        ]);

        try {
            // Delegar todo o processamento para o service
            $transferService->processTransfer($this->transfer);
            
        } catch (\Exception $e) {
            Log::error('Erro ao processar transferência', [
                'transfer_id' => $this->transfer->id,
                'error' => $e->getMessage()
            ]);

            // Relançar exceção para que o job possa ser retentado se necessário
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Job de transferência falhou', [
            'transfer_id' => $this->transfer->id,
            'error' => $exception->getMessage()
        ]);
        
        // Marcar a transferência como falha
        $this->transfer->status = 'failed';
        $this->transfer->error_message = $exception->getMessage();
        $this->transfer->save();
    }
}