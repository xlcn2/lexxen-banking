<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Otimização para evitar N+1 queries
        $wallets = Auth::user()->accounts()
            ->with(['wallets.account'])
            ->get()
            ->flatMap->wallets
            ->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'type' => $wallet->type,
                    'status' => $wallet->status,
                    'balance' => $wallet->balance,
                    'account' => [
                        'id' => $wallet->account->id,
                        'number' => $wallet->account->number,
                    ],
                ];
            });

        return view('wallets.index', compact('wallets'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $accountId = $request->query('account_id');
        $account = Account::findOrFail($accountId);
        
        // Verificar se a conta pertence ao usuário autenticado
        if ($account->accountable_id !== Auth::id() || $account->accountable_type !== get_class(Auth::user())) {
            abort(403, 'Você não tem permissão para criar carteiras nesta conta.');
        }

        return view('wallets.create', compact('account'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account_id' => 'required|exists:accounts,id',
        ]);

        $account = Account::findOrFail($validated['account_id']);
        
        // Verificar se a conta pertence ao usuário autenticado
        if ($account->accountable_id !== Auth::id() || $account->accountable_type !== get_class(Auth::user())) {
            abort(403, 'Você não tem permissão para criar carteiras nesta conta.');
        }

        // Verificar se a conta está ativa
        if (!$account->isActive()) {
            return redirect()->back()->with('error', 'Não é possível criar carteiras em contas bloqueadas.');
        }

        // Criar a carteira (sempre do tipo 'wallet', nunca 'default')
        $wallet = $account->wallets()->create([
            'name' => $validated['name'],
            'balance' => 0, // Saldo inicial é zero
            'type' => 'wallet', // Usuários só podem criar carteiras secundárias
            'status' => 'active',
        ]);

        return redirect()->route('wallets.index')
            ->with('success', 'Carteira criada com sucesso!');
    }

    /**
     * Update the status of a wallet.
     */
    public function updateStatus(Request $request, Wallet $wallet)
    {
        // Verificar se a carteira pertence ao usuário autenticado
        $this->authorize('update', $wallet);

        // Verificar se está tentando desativar uma carteira DEFAULT
        if ($wallet->isDefault() && $request->status === 'inactive') {
            return redirect()->back()->with('error', 'Não é possível desativar a carteira principal.');
        }

        // Verificar se há saldo na carteira antes de desativar
        if ($request->status === 'inactive' && $wallet->balance > 0) {
            return redirect()->back()->with('error', 'Não é possível desativar uma carteira com saldo. Transfira o saldo primeiro.');
        }

        $wallet->update(['status' => $request->status]);

        return redirect()->route('wallets.index')
            ->with('success', 'Status da carteira atualizado com sucesso!');
    }
}