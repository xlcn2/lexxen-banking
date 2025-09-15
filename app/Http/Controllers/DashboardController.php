<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the dashboard page.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Obter as contas do usuário (pessoa física ou jurídica)
        $accounts = [];
        
        if (method_exists($user, 'accounts')) {
            $accounts = $user->accounts()
                ->with('wallets')
                ->get()
                ->map(function($account) {
                    return [
                        'id' => $account->id,
                        'number' => $account->number,
                        'status' => $account->status,
                        'total_balance' => $account->total_balance,
                    ];
                });
        }
        
        return view('dashboard', compact('accounts'));
    }
}
