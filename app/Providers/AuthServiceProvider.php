<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Wallet;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Statement::class => StatementPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Registrar Gates
        Gate::define('update-wallet', function ($user, Wallet $wallet) {
            // Obter as contas do usuário
            $userAccountIds = $user->accounts->pluck('id')->toArray();
            
            // Verificar se a carteira pertence a uma das contas do usuário
            return in_array($wallet->account_id, $userAccountIds);
        });
        
        // Outros Gates...
    }
}