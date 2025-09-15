@extends('layouts.app')

@section('title', 'Extrato')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Extrato</div>

                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('statements.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="wallet_id" class="form-label">Carteira</label>
                                <select class="form-select" id="wallet_id" name="wallet_id">
                                    <option value="">Todas as carteiras</option>
                                    @foreach($wallets as $wallet)
                                        <option value="{{ $wallet['id'] }}" {{ $walletId == $wallet['id'] ? 'selected' : '' }}>
                                            {{ $wallet['name'] }} ({{ $wallet['account_number'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Data Inicial</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">Data Final</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                                <a href="{{ route('statements.index') }}" class="btn btn-outline-secondary">Limpar</a>
                            </div>
                        </div>
                    </form>

                    <!-- Tabela de Extratos -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Carteira</th>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Saldo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($statements as $statement)
                                    <tr>
                                        <td>{{ $statement->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            {{ $statement->wallet->name }} <br>
                                            <small class="text-muted">Conta: {{ $statement->wallet->account->number }}</small>
                                        </td>
                                        <td>
                                            @if($statement->transfer)
                                                @if($statement->transfer->source_wallet_id == $statement->wallet_id)
                                                    Transferência enviada para 
                                                    {{ $statement->transfer->destinationWallet->account->number }}
                                                @else
                                                    Transferência recebida de 
                                                    {{ $statement->transfer->sourceWallet->account->number }}
                                                @endif
                                            @else
                                                {{ $statement->description }}
                                            @endif
                                        </td>
                                        <td class="{{ $statement->amount < 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                            {{ $statement->amount < 0 ? '-' : '+' }} R$ {{ number_format(abs($statement->amount), 2, ',', '.') }}
                                        </td>
                                        <td>
                                            R$ {{ number_format($statement->balance_after, 2, ',', '.') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('statements.show', $statement) }}" class="btn btn-sm btn-outline-secondary">
                                                Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Nenhuma transação encontrada para o período selecionado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $statements->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection