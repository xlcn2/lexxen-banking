<!-- resources/views/transfers/create.blade.php -->
@extends('layouts.app')

@section('title', 'Nova Transferência')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Nova Transferência</div>

                <div class="card-body" x-data="{ 
                    transferType: 'internal', 
                    wallets: {{ json_encode($wallets) }},
                    otherWallets: {{ json_encode($otherWallets) }},
                    sourceWalletId: '',
                    destinationWalletId: '',
                    amount: ''
                }">
                    <div class="mb-4">
                        <div class="btn-group w-100">
                            <button 
                                type="button" 
                                class="btn" 
                                :class="transferType === 'internal' ? 'btn-primary' : 'btn-outline-primary'"
                                @click="transferType = 'internal'"
                            >
                                Entre minhas carteiras
                            </button>
                            <button 
                                type="button" 
                                class="btn" 
                                :class="transferType === 'external' ? 'btn-primary' : 'btn-outline-primary'"
                                @click="transferType = 'external'"
                            >
                                Para outra conta
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('transfers.store') }}" method="POST">
                        @csrf
                        
                        <input type="hidden" name="transfer_type" x-model="transferType">
                        
                        <div class="mb-3">
                            <label for="source_wallet_id" class="form-label">Carteira de Origem</label>
                            <select 
                                name="source_wallet_id" 
                                id="source_wallet_id" 
                                class="form-select @error('source_wallet_id') is-invalid @enderror"
                                x-model="sourceWalletId"
                                required
                            >
                                <option value="">Selecione uma carteira</option>
                                <template x-for="wallet in wallets" :key="wallet.id">
                                    <option 
                                        :value="wallet.id" 
                                        x-text="`${wallet.name} (R$ ${wallet.balance.toFixed(2)})`"
                                    ></option>
                                </template>
                            </select>
                            @error('source_wallet_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Destino para transferências internas -->
                        <div class="mb-3" x-show="transferType === 'internal'">
                            <label for="destination_wallet_id" class="form-label">Carteira de Destino</label>
                            <select 
                                name="destination_wallet_id" 
                                id="destination_wallet_id" 
                                class="form-select @error('destination_wallet_id') is-invalid @enderror"
                                x-model="destinationWalletId"
                                x-bind:required="transferType === 'internal'"
                            >
                                <option value="">Selecione uma carteira</option>
                                <template x-for="wallet in wallets" :key="wallet.id">
                                    <option 
                                        :value="wallet.id" 
                                        x-text="wallet.name"
                                        :disabled="wallet.id === sourceWalletId"
                                    ></option>
                                </template>
                            </select>
                            @error('destination_wallet_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Destino para transferências externas -->
                        <div class="mb-3" x-show="transferType === 'external'">
                            <label for="destination_wallet_id" class="form-label">Conta de Destino</label>
                            <select 
                                name="destination_wallet_id" 
                                id="external_destination_wallet_id" 
                                class="form-select @error('destination_wallet_id') is-invalid @enderror"
                                x-model="destinationWalletId"
                                x-bind:required="transferType === 'external'"
                            >
                                <option value="">Selecione uma conta de destino</option>
                                <template x-for="wallet in otherWallets" :key="wallet.id">
                                    <option 
                                        :value="wallet.id" 
                                        x-text="`${wallet.account_number} (${wallet.user_name})`"
                                    ></option>
                                </template>
                            </select>
                            @error('destination_wallet_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label for="amount" class="form-label">Valor</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input 
                                    type="number" 
                                    name="amount" 
                                    id="amount" 
                                    class="form-control @error('amount') is-invalid @enderror" 
                                    min="0.01" 
                                    step="0.01" 
                                    x-model="amount"
                                    required
                                >
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Transferir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection