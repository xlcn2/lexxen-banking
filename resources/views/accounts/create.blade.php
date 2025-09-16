@extends('layouts.app')

@section('title', 'Criar Nova Conta')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Criar Nova Conta</div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('accounts.store') }}">
                        @csrf
                        
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">Informações importantes:</h5>
                            <ul class="mb-0">
                                <li>Uma carteira principal será criada automaticamente com a sua conta.</li>
                                <li>O saldo inicial da conta será zero.</li>
                                <li>Você poderá criar carteiras adicionais depois.</li>
                                <li>Apenas usuários aprovados podem criar contas.</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('accounts.index') }}" class="btn btn-secondary">Voltar</a>
                            <button type="submit" class="btn btn-primary">Criar Conta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection