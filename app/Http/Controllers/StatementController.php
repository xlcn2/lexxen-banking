<?php

namespace App\Http\Controllers;

use App\Models\Statement;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StatementController extends Controller
{
    /**
     * Display a listing of statements.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $accountIds = $user->accounts->pluck('id');
        
        // Obter carteira específica se o ID for fornecido
        $walletId = $request->query('wallet_id');
        
        // Obter todas as carteiras do usuário para o filtro
        $wallets = Wallet::whereIn('account_id', $accountIds)
            ->with('account')
            ->get()
            ->map(function($wallet) {
                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'account_number' => $wallet->account->number,
                ];
            });
        
        // Preparar a consulta base para extratos
        $query = Statement::whereHas('wallet', function($q) use ($accountIds) {
            $q->whereIn('account_id', $accountIds);
        });
        
        // Filtrar por carteira específica
        if ($walletId) {
            $query->where('wallet_id', $walletId);
        }
        
        // Filtrar por período
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', Carbon::parse($startDate));
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', Carbon::parse($endDate));
        }
        
        // Obter extratos com eager loading para evitar N+1
        $statements = $query->with([
                'wallet', 
                'wallet.account',
                'transfer',
                'transfer.sourceWallet',
                'transfer.destinationWallet'
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return view('statements.index', compact('statements', 'wallets', 'walletId', 'startDate', 'endDate'));
    }
    
    /**
     * Display the specified statement.
     */
    public function show(Statement $statement)
    {
        // Verificar se o extrato pertence a uma carteira do usuário autenticado
        $this->authorize('view', $statement);
        
        // Eager loading para evitar N+1
        $statement->load([
            'wallet', 
            'wallet.account',
            'transfer',
            'transfer.sourceWallet',
            'transfer.destinationWallet'
        ]);
        
        return view('statements.show', compact('statement'));
    }
}