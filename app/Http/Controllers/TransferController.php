<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\DTOs\Transfer\TransferDTO;
use App\Services\TransferService;
use App\Http\Resources\Transfer\TransferResource;
use App\Models\Wallet;
use App\Models\Transfer;

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
        $request->validate([
            'source_wallet_id' => 'required|exists:wallets,id',
            'destination_wallet_id' => 'required|exists:wallets,id|different:source_wallet_id',
            'amount' => 'required|numeric|min:0.01',
        ]);
        
        try {
            $dto = new TransferDTO(
                source_wallet_id: $request->source_wallet_id,
                destination_wallet_id: $request->destination_wallet_id,
                amount: (float)$request->amount,
            );
            
            $transfer = $this->transferService->transfer($dto);
            
            return redirect()->route('transfers.index')
                ->with('success', 'Transferência iniciada com sucesso! Ela será processada em breve.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
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