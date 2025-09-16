<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index()
    {
        // Otimização para evitar N+1 queries
        $accounts = Auth::user()->accounts()
            ->with('wallets')  // Eager loading para evitar N+1
            ->get()
            ->map(function($account) {
                // Calcular o saldo total da conta somando os saldos das carteiras
                $totalBalance = $account->wallets->sum('balance');
                
                return [
                    'id' => $account->id,
                    'number' => $account->number,
                    'status' => $account->status,
                    'total_balance' => (float) $totalBalance,
                ];
            });
        
        return view('accounts.index', compact('accounts'));
    }

    /**
     * Show the form for creating a new account.
     */
    public function create()
    {
        // Verificar se o usuário está aprovado
        if (Auth::user()->status !== 'approved') {
            return redirect()->route('accounts.index')
                ->with('error', 'Apenas usuários aprovados podem criar contas.');
        }
        
        return view('accounts.create');
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request)
    {
        // Verificar se o usuário está aprovado
        if (Auth::user()->status !== 'approved') {
            return redirect()->route('accounts.index')
                ->with('error', 'Apenas usuários aprovados podem criar contas.');
        }

        // Gerar número único de conta
        $accountNumber = $this->generateUniqueAccountNumber();

        // Criar a conta
        $account = Auth::user()->accounts()->create([
            'number' => $accountNumber,
            'status' => 'active', // Conta inicia ativa
        ]);

        // Criar a carteira DEFAULT automaticamente
        $account->wallets()->create([
            'name' => 'Principal',
            'balance' => 0, // Saldo inicial é zero
            'type' => 'default',
            'status' => 'active',
        ]);

        return redirect()->route('accounts.index')
            ->with('success', 'Conta criada com sucesso!');
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account)
    {
        // Verificar se a conta pertence ao usuário autenticado
        if ($account->accountable_id !== Auth::id() || $account->accountable_type !== get_class(Auth::user())) {
            abort(403, 'Você não tem permissão para visualizar esta conta.');
        }

        // Eager loading para evitar N+1 queries
        $account->load(['wallets' => function($query) {
            $query->withCount('statements');
        }]);
        
        // Calcular o saldo total da conta
        $account->total_balance = $account->wallets->sum('balance');

        return view('accounts.show', compact('account'));
    }

    /**
     * Update the status of the account.
     */
    public function updateStatus(Request $request, Account $account)
    {
        // Verificar se a conta pertence ao usuário autenticado
        if ($account->accountable_id !== Auth::id() || $account->accountable_type !== get_class(Auth::user())) {
            abort(403, 'Você não tem permissão para atualizar esta conta.');
        }

        $validated = $request->validate([
            'status' => 'required|in:active,blocked',
        ]);

        $account->update(['status' => $validated['status']]);

        return redirect()->route('accounts.show', $account)
            ->with('success', 'Status da conta atualizado com sucesso!');
    }

    /**
     * Generate a unique account number.
     */
    private function generateUniqueAccountNumber()
    {
        do {
            $number = mt_rand(10000000, 99999999);
        } while (Account::where('number', $number)->exists());

        return $number;
    }
}