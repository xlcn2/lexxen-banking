<!-- resources/views/accounts/index.blade.php -->
@extends('layouts.app')

@section('title', 'Minhas Contas')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Minhas Contas</span>
                    <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary">Nova Conta</a>
                </div>

                <div class="card-body">
                    <div class="row" x-data="{ accounts: {{ json_encode($accounts) }} }">
                        <template x-for="account in accounts" :key="account.id">
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <h5 class="card-title" x-text="'Conta ' + account.number"></h5>
                                            <span 
                                                x-text="account.status === 'active' ? 'Ativa' : 'Bloqueada'"
                                                :class="{
                                                    'badge bg-success': account.status === 'active',
                                                    'badge bg-danger': account.status !== 'active'
                                                }"
                                            ></span>
                                        </div>
                                        <h3 class="mb-3" x-text="'R$ ' + account.total_balance.toFixed(2)"></h3>
                                        <div class="d-flex gap-2">
                                            <a :href="`/accounts/${account.id}`" class="btn btn-sm btn-outline-primary">
                                                Ver detalhes
                                            </a>
                                            <a :href="`/accounts/${account.id}/wallets/create`" class="btn btn-sm btn-outline-secondary">
                                                Nova Carteira
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <div class="col-12 text-center" x-show="accounts.length === 0">
                            <p class="text-muted my-5">Você não possui nenhuma conta. Crie uma nova conta para começar!</p>
                            <a href="{{ route('accounts.create') }}" class="btn btn-primary">Criar Conta</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection