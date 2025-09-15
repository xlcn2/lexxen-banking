@extends('layouts.app')

@section('title', 'Detalhes do Extrato')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Detalhes da Transação</span>
                    <a href="{{ route('statements.index') }}" class="btn btn-sm btn-secondary">Voltar</a>
                </div>

                <div class="card-body">
                    <div class="mb-4 text-center">
                        <h4 class="{{ $statement->amount < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $statement->amount < 0 ? '-' : '+' }} R$ {{ number_format(abs($statement->amount), 2, ',', '.') }}
                        </h4>
                        <p class="text-muted">{{ $statement->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">Carteira</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p class="mb-1"><strong>Nome:</strong> {{ $statement->wallet->name }}</p>
                                    <p class="mb-1"><strong>Conta:</strong> {{ $statement->wallet->account->number }}</p>
                                    <p class="mb-0"><strong>Saldo após:</strong> R$ {{ number_format($statement->balance_after, 2, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                        
                        @if($statement->transfer)
                        <div class="col-md-6">
                            <h5 class="mb-3">
                                @if($statement->transfer->source_wallet_id == $statement->wallet_id)
                                    Destinatário
                                @else
                                    Remetente
                                @endif
                            </h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    @if($statement->transfer->source_wallet_id == $statement->wallet_id)
                                        <p class="mb-1"><strong>Carteira:</strong> {{ $statement->transfer->destinationWallet->name }}</p>
                                        <p class="mb-0"><strong>Conta:</strong> {{ $statement->transfer->destinationWallet->account->number }}</p>
                                    @else
                                        <p class="mb-1"><strong>Carteira:</strong> {{ $statement->transfer->sourceWallet->name }}</p>
                                        <p class="mb-0"><strong>Conta:</strong> {{ $statement->transfer->sourceWallet->account->number }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($statement->description)
                        <div class="mb-4">
                            <h5 class="mb-2">Descrição</h5>
                            <p>{{ $statement->description }}</p>
                        </div>
                    @endif

                    @if($statement->transfer)
                        <div class="mb-4">
                            <h5 class="mb-2">Detalhes da Transferência</h5>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-{{ $statement->transfer->status === 'completed' ? 'success' : ($statement->transfer->status === 'pending' ? 'warning' : 'danger') }}">
                                    {{ $statement->transfer->status === 'completed' ? 'Concluída' : ($statement->transfer->status === 'pending' ? 'Pendente' : 'Falha') }}
                                </span>
                            </p>
                            <p><strong>ID:</strong> {{ $statement->transfer->id }}</p>
                            @if($statement->transfer->completed_at)
                                <p><strong>Concluída em:</strong> {{ $statement->transfer->completed_at->format('d/m/Y H:i:s') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection