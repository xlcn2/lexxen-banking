<!-- resources/views/wallets/index.blade.php -->
@extends('layouts.app')

@section('title', 'Minhas Carteiras')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Minhas Carteiras</div>

                <div class="card-body" x-data="{ wallets: {{ json_encode($wallets) }} }">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Conta</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Saldo</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="wallet in wallets" :key="wallet.id">
                                    <tr>
                                        <td x-text="wallet.name"></td>
                                        <td x-text="wallet.account.number"></td>
                                        <td>
                                            <span 
                                                x-text="wallet.type === 'default' ? 'Principal' : 'Secundária'"
                                                :class="{
                                                    'badge bg-primary': wallet.type === 'default',
                                                    'badge bg-secondary': wallet.type === 'wallet'
                                                }"
                                            ></span>
                                        </td>
                                        <td>
                                            <span 
                                                x-text="wallet.status === 'active' ? 'Ativa' : 'Inativa'"
                                                :class="{
                                                    'badge bg-success': wallet.status === 'active',
                                                    'badge bg-danger': wallet.status === 'inactive'
                                                }"
                                            ></span>
                                        </td>
                                        <td class="fw-bold" x-text="'R$ ' + wallet.balance.toFixed(2)"></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a :href="`/statements?wallet_id=${wallet.id}`" class="btn btn-outline-secondary">
                                                    Extrato
                                                </a>
                                                <a :href="`/transfers/create?source=${wallet.id}`" class="btn btn-outline-primary">
                                                    Transferir
                                                </a>
                                                <button 
                                                    class="btn btn-outline-danger"
                                                    :disabled="wallet.type === 'default' || wallet.balance > 0"
                                                    @click="if(confirm('Desativar esta carteira?')) document.getElementById(`deactivate-wallet-${wallet.id}`).submit()"
                                                    x-show="wallet.status === 'active'"
                                                >
                                                    Desativar
                                                </button>
                                                <form :id="`deactivate-wallet-${wallet.id}`" :action="`/wallets/${wallet.id}/status`" method="POST" class="d-none">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="inactive">
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                <tr x-show="wallets.length === 0">
                                    <td colspan="6" class="text-center py-3">Você não possui nenhuma carteira.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection