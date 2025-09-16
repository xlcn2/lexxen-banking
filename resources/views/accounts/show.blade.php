@extends('layouts.app')

@section('title', 'Detalhes da Conta')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Conta {{ $account->number }}</span>
                    <span class="badge {{ $account->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                        {{ $account->status === 'active' ? 'Ativa' : 'Bloqueada' }}
                    </span>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="mb-3">Saldo Total: R$ {{ number_format($account->total_balance, 2, ',', '.') }}</h3>
                            
                            @if($account->status === 'active')
                                <a href="{{ route('wallets.create', ['account_id' => $account->id]) }}" class="btn btn-primary mb-3">
                                    Nova Carteira
                                </a>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Informações da Conta</h5>
                                    <p class="mb-1"><strong>Número:</strong> {{ $account->number }}</p>
                                    <p class="mb-1"><strong>Status:</strong> {{ $account->status === 'active' ? 'Ativa' : 'Bloqueada' }}</p>
                                    <p class="mb-1"><strong>Data de Criação:</strong> {{ $account->created_at->format('d/m/Y') }}</p>
                                    <p class="mb-0"><strong>Carteiras:</strong> {{ $account->wallets->count() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Carteiras da Conta</div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Saldo</th>
                                    <th>Movimentações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($account->wallets as $wallet)
                                    <tr>
                                        <td>{{ $wallet->name }}</td>
                                        <td>
                                            <span class="badge {{ $wallet->type === 'default' ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ $wallet->type === 'default' ? 'Principal' : 'Secundária' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $wallet->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                                {{ $wallet->status === 'active' ? 'Ativa' : 'Inativa' }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">R$ {{ number_format($wallet->balance, 2, ',', '.') }}</td>
                                        <td>{{ $wallet->statements_count }} transações</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('statements.index', ['wallet_id' => $wallet->id]) }}" class="btn btn-outline-secondary">
                                                    Extrato
                                                </a>
                                                @if($wallet->status === 'active')
                                                    <a href="{{ route('transfers.create', ['source' => $wallet->id]) }}" class="btn btn-outline-primary">
                                                        Transferir
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Esta conta não possui carteiras.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Voltar para Contas</a>
            </div>
        </div>
    </div>
</div>
@endsection