@extends('layouts.app')

@section('title', 'Minhas Transferências')

@section('content')
<!-- Resto do conteúdo da página -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Histórico de Transferências</span>
                    <a href="{{ route('transfers.create') }}" class="btn btn-sm btn-primary">Nova Transferência</a>
                </div>

                <div class="card-body">
                  

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>De</th>
                                    <th>Para</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            {{ $transfer->sourceWallet->account->number }} <br>
                                            <small class="text-muted">{{ $transfer->sourceWallet->name }}</small>
                                        </td>
                                        <td>
                                            {{ $transfer->destinationWallet->account->number }} <br>
                                            <small class="text-muted">{{ $transfer->destinationWallet->name }}</small>
                                        </td>
                                        <td class="fw-bold">R$ {{ number_format($transfer->amount, 2, ',', '.') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ $transfer->status === 'completed' ? 'Concluída' : ($transfer->status === 'pending' ? 'Pendente' : 'Falha') }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-outline-secondary">
                                                Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3">Você não possui nenhuma transferência.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $transfers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection