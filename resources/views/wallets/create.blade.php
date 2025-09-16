@extends('layouts.app')

@section('title', 'Criar Nova Carteira')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Criar Nova Carteira</div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('wallets.store') }}">
                        @csrf
                        
                        <input type="hidden" name="account_id" value="{{ $account->id }}">
                        
                        <div class="mb-3">
                            <label for="account_number" class="form-label">Conta</label>
                            <input type="text" class="form-control" id="account_number" value="{{ $account->number }}" disabled>
                            <div class="form-text">A carteira será vinculada a esta conta.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome da Carteira</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Escolha um nome descritivo (ex: Investimentos, Despesas Pessoais)</div>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">Informações importantes:</h5>
                            <ul class="mb-0">
                                <li>A carteira será criada com saldo inicial zero.</li>
                                <li>Você pode criar múltiplas carteiras para diferentes finalidades.</li>
                                <li>Para desativar uma carteira, é necessário transferir todo o saldo primeiro.</li>
                                <li>A carteira principal (tipo DEFAULT) não pode ser desativada.</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('wallets.index') }}" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Criar Carteira</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection