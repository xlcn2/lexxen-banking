@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Dashboard</span>
                </div>

                <div class="card-body">
                    <h4 class="mb-4">Suas Contas</h4>
                    
                    <div class="row" x-data="{ accounts: {{ json_encode($accounts ?? []) }} }">
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
                                        <a :href="`/accounts/${account.id}`" class="btn btn-sm btn-outline-primary">
                                            Ver detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <div class="col-12 text-center" x-show="accounts.length === 0">
                            <p class="text-muted my-5">Você não possui nenhuma conta ativa. Crie uma conta para começar!</p>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h4 class="mb-3">Ações Rápidas</h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('transfers.create') }}" class="btn btn-primary">Nova Transferência</a>
                            <a href="{{ route('accounts.create') }}" class="btn btn-outline-secondary">Criar Nova Conta</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
