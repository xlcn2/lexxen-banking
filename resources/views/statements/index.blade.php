<!-- resources/views/statements/index.blade.php -->
@extends('layouts.app')

@section('title', 'Extratos')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Extratos</div>

                <div class="card-body" x-data="{ 
                    selectedWallet: '{{ request('wallet_id', '') }}',
                    startDate: '{{ request('start_date', '') }}',
                    endDate: '{{ request('end_date', '') }}',
                    wallets: {{ json_encode($wallets) }},
                    statements: {{ json_encode($statements) }}
                }">
                    <!-- Filtros -->
                    <form action="{{ route('statements.index') }}" method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="wallet_id" class="form-label">Carteira</label>
                                <select 
                                    name="wallet_id" 
                                    id="wallet_id" 
                                    class="form-select"
                                    x-model="selectedWallet"
                                    @change="this.form.submit()"
                                >
                                    <option value="">Todas as carteiras</option>
                                    <template x-for="wallet in wallets" :key="wallet.id">
                                        <option 
                                            :value="wallet.id" 
                                            x-text="wallet.name"
                                        ></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Data Inicial</label>
                                <input 
                                    type="date" 
                                    name="start_date" 
                                    id="start_date" 
                                    class="form-control"
                                    x-model="startDate"
                                    @change="this.form.submit()"
                                >
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Data Final</label>
                                <input 
                                    type="date" 
                                    name="end_date" 
                                    id="end_date" 
                                    class="form-control"
                                    x-model="endDate"
                                    @change="this.form.submit()"
                                >
                            </div>
                        </div>
                    </form>
                    
                    <!-- Tabela de extratos -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Carteira</th>
                                    <th>Valor</th>
                                    <th>Saldo Após</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="statement in statements.data" :key="statement.id">
                                    <tr>
                                        <td x-text="new Date(statement.created_at).toLocaleString('pt-BR')"></td>
                                        <td>
                                            <span 
                                                x-text="statement.type === 'credit' ? 'Crédito' : 'Débito'"
                                                :class="{
                                                    'badge bg-success': statement.type === 'credit',
                                                    'badge bg-danger': statement.type === 'debit'
                                                }"
                                            ></span>
                                        </td>
                                        <td x-text="statement.wallet.name"></td>
                                        <td 
                                            :class="{
                                                'text-success fw-bold': statement.type === 'credit',
                                                'text-danger fw-bold': statement.type === 'debit'
                                            }"
                                            x-text="(statement.type === 'credit' ? '+' : '-') + ' R$ ' + statement.amount.toFixed(2)"
                                        ></td>
                                        <td class="fw-bold" x-text="'R$ ' + statement.balance_after.toFixed(2)"></td>
                                    </tr>
                                </template>
                                
                                <tr x-show="statements.data.length === 0">
                                    <td colspan="5" class="text-center py-3">Nenhuma transação encontrada.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $statements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection