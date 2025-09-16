<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; 
use App\DTOs\Transfer\TransferDTO;
use App\Services\TransferService;
use App\Http\Resources\Transfer\TransferResource;
use App\Models\Wallet;
use App\Models\Transfer;
use App\Jobs\ProcessTransfer;

class TransferController extends Controller
{
    protected $transferService;

    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Display a listing of transfers.
     */
    public function index()
    {
        $user = Auth::user();
        $accountIds = $user->accounts->pluck('id');
        
        // Obter todas as carteiras do usuário
        $walletIds = Wallet::whereIn('account_id', $accountIds)->pluck('id');
        
        // Obter transferências de/para as carteiras do usuário
        $transfers = Transfer::where(function($query) use ($walletIds) {
                $query->whereIn('source_wallet_id', $walletIds)
                      ->orWhereIn('destination_wallet_id', $walletIds);
            })
            ->with(['sourceWallet', 'destinationWallet'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return view('transfers.index', compact('transfers'));
    }

    /**
     * Show the form for creating a new transfer.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $accountIds = $user->accounts->pluck('id');
        
        // Obter todas as carteiras ativas do usuário
        $wallets = Wallet::whereIn('account_id', $accountIds)
            ->where('status', 'active')
            ->with('account')
            ->get()
            ->map(function($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'balance' => (float)$wallet->balance,
                    'account_id' => $wallet->account_id,
                ];
            });
        
        // Obter carteiras default de outros usuários para transferência externa
        $otherWallets = Wallet::whereNotIn('account_id', $accountIds)
            ->where('type', 'default')
            ->where('status', 'active')
            ->with(['account.accountable'])
            ->get()
            ->map(function($wallet) {
                $userName = $wallet->account->accountable->name ?? 
                    $wallet->account->accountable->company_name ?? 'Usuário';
                
                return [
                    'id' => $wallet->id,
                    'account_number' => $wallet->account->number,
                    'user_name' => $userName,
                ];
            });
        
        // Pré-selecionar carteira de origem, se especificada
        $sourceWalletId = $request->query('source');
        
        return view('transfers.create', compact('wallets', 'otherWallets', 'sourceWalletId'));
    }

    /**
     * Store a newly created transfer.
     */
    public function store(Request $request)
    {
     // Validar request
     $validated = $request->validate([
        'source_wallet_id' => 'required|exists:wallets,id',
        'destination_wallet_id' => 'required|exists:wallets,id',
        'amount' => 'required|numeric|min:0.01',
        'idempotency_key' => 'sometimes|string'
    ]);

    // Buscar carteira de origem com relacionamento de conta
    $sourceWallet = Wallet::with('account')->findOrFail($validated['source_wallet_id']);
    
    // Verificar se a conta está bloqueada
    if ($sourceWallet->account->status === 'blocked') {
        return redirect()->route('transfers.index')
            ->with('error', 'A conta de origem está bloqueada. Não é possível realizar transferências.');
    }
    
    // Verificar se a carteira está ativa
    if ($sourceWallet->status !== 'active') {
        return redirect()->route('transfers.index')
            ->with('error', 'A carteira de origem não está ativa');
    }

    // Verificar carteira de destino
    $destinationWallet = Wallet::with('account')->findOrFail($validated['destination_wallet_id']);
    
    // Verificar se a carteira de destino está ativa
    if ($destinationWallet->status !== 'active') {
        return redirect()->route('transfers.index')
            ->with('error', 'A carteira de destino não está ativa');
    }

    // Verificar saldo
    if ($sourceWallet->balance < $validated['amount']) {
        return redirect()->route('transfers.index')
            ->with('error', 'Saldo insuficiente para realizar a transferência');
    }

    
        // Gerar chave de idempotência se não fornecida
        if (!isset($validated['idempotency_key'])) {
            $validated['idempotency_key'] = Str::uuid()->toString();
        }
    
        // Formatar valor para exibição
        $formattedAmount = number_format($validated['amount'], 2, ',', '.');
    
        // Obter informações das carteiras para mensagem mais detalhada
        $sourceWalletName = $sourceWallet->name;
        $destinationWalletName = $destinationWallet->name;
    
        try {
            // Disparar job para processamento assíncrono
            ProcessTransfer::dispatch($validated);
            
            // Redirecionar com mensagem de sucesso
            return redirect()->route('transfers.index')
                ->with('success', "Transferência de R$ {$formattedAmount} enviada para processamento da carteira {$sourceWalletName} para {$destinationWalletName}. Aguarde a confirmação.");
        } catch (\Exception $e) {
            // Em caso de erro, redirecionar com mensagem de erro
            return redirect()->route('transfers.index')
                ->with('error', 'Erro ao processar transferência: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transfer.
     */
    public function show(string $id)
    {
        $transfer = $this->transferService->findById($id);
        
        // Verificar se o usuário tem permissão para ver esta transferência
        $this->authorize('view', $transfer);
        
        return view('transfers.show', compact('transfer'));
    }
}