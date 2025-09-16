<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Wallet;
use App\Models\Transfer;
use App\Models\Statement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                    'balance' => (float)$wallet->balance, // Garantir que é tratado como float para o JSON
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

        // Verificar se a conta está ativa
        if ($account->status !== 'active') {
            return redirect()->route('accounts.index')
                ->with('error', 'Não é possível criar carteiras em contas bloqueadas.');
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
        if ($account->status !== 'active') {
            return redirect()->back()->with('error', 'Não é possível criar carteiras em contas bloqueadas.');
        }

        // Verificar se já existe uma carteira com este nome nesta conta
        $existingWallet = $account->wallets()->where('name', $validated['name'])->first();
        if ($existingWallet) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Já existe uma carteira com este nome nesta conta.');
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
        if (Gate::denies('update-wallet', $wallet)) {
            abort(403, 'Você não tem permissão para atualizar esta carteira.');
        }

        // Verificar se está tentando desativar uma carteira DEFAULT
        if ($wallet->type === 'default' && $request->status === 'inactive') {
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


    /**
     * Handle wallet deactivation with balance transfer.
     */
    public function deactivateWithTransfer(Request $request, Wallet $wallet)
    {
        // Verificar se a carteira pertence ao usuário autenticado
        if (Gate::denies('update-wallet', $wallet)) {
            abort(403, 'Você não tem permissão para atualizar esta carteira.');
        }

        // Verificar se está tentando desativar uma carteira DEFAULT
        if ($wallet->type === 'default') {
            return redirect()->back()->with('error', 'Não é possível desativar a carteira principal.');
        }

        // Validar os dados do formulário
        $validated = $request->validate([
            'destination_wallet_id' => 'required|exists:wallets,id',
        ]);

        // Verificar se a carteira de destino pertence ao usuário e está ativa
        $destinationWallet = Wallet::findOrFail($validated['destination_wallet_id']);
        if (Gate::denies('update-wallet', $destinationWallet)) {
            return redirect()->back()->with('error', 'A carteira de destino selecionada não é válida.');
        }

        if ($destinationWallet->status !== 'active') {
            return redirect()->back()->with('error', 'A carteira de destino deve estar ativa.');
        }

        // Verificar se as carteiras são diferentes
        if ($wallet->id === $destinationWallet->id) {
            return redirect()->back()->with('error', 'A carteira de destino não pode ser a mesma que será desativada.');
        }

        // Iniciar transação para garantir atomicidade
        return DB::transaction(function () use ($wallet, $destinationWallet) {
            // Verificar se há saldo para transferir
            if ($wallet->balance > 0) {
                // Transferir o saldo
                $destinationWallet->balance += $wallet->balance;
                $wallet->balance = 0;
                
                $destinationWallet->save();
                $wallet->save();
                
                // Registrar a transferência no histórico
                $transfer = Transfer::create([
                    'source_wallet_id' => $wallet->id,
                    'destination_wallet_id' => $destinationWallet->id,
                    'amount' => $wallet->balance,
                    'status' => 'completed',
                    'idempotency_key' => Str::uuid()->toString(),
                    'description' => 'Transferência automática para desativação de carteira'
                ]);
                
                // Criar statements para a transferência
                Statement::create([
                    'wallet_id' => $wallet->id,
                    'transfer_id' => $transfer->id,
                    'type' => 'debit',
                    'amount' => $transfer->amount,
                    'balance_after' => 0
                ]);
                
                Statement::create([
                    'wallet_id' => $destinationWallet->id,
                    'transfer_id' => $transfer->id,
                    'type' => 'credit',
                    'amount' => $transfer->amount,
                    'balance_after' => $destinationWallet->balance
                ]);
            }
            
            // Desativar a carteira
            $wallet->status = 'inactive';
            $wallet->save();
            
            return redirect()->route('wallets.index')
                ->with('success', 'Carteira desativada com sucesso. O saldo foi transferido para a carteira selecionada.');
        });
    }
}