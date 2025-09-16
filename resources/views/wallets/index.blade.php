@extends('layouts.app')

@section('title', 'Minhas Carteiras')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Minhas Carteiras</span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="newWalletDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Nova Carteira
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="newWalletDropdown">
                            @foreach(Auth::user()->accounts as $account)
                                @if($account->status === 'active')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('wallets.create', ['account_id' => $account->id]) }}">
                                            Conta {{ $account->number }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
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
                                <template x-data="{ wallets: {{ json_encode($wallets) }} }" x-for="wallet in wallets" :key="wallet.id">
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
                                        <td class="fw-bold" x-text="'R$ ' + parseFloat(wallet.balance).toFixed(2).replace('.', ',')"></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a :href="`/statements?wallet_id=${wallet.id}`" class="btn btn-outline-secondary">
                                                    Extrato
                                                </a>
                                                <a :href="`/transfers/create?source=${wallet.id}`" class="btn btn-outline-primary" x-show="wallet.status === 'active'">
                                                    Transferir
                                                </a>
                                                <!-- Botão para carteiras sem saldo -->
                                                <button 
                                                    class="btn btn-outline-danger"
                                                    :disabled="wallet.type === 'default'"
                                                    @click="if(confirm('Desativar esta carteira?')) document.getElementById(`deactivate-wallet-${wallet.id}`).submit()"
                                                    x-show="wallet.status === 'active' && wallet.balance == 0 && wallet.type !== 'default'"
                                                >
                                                    Desativar
                                                </button>
                                                <!-- Botão para carteiras com saldo - abre modal -->
                                                <button 
                                                    class="btn btn-outline-danger"
                                                    :disabled="wallet.type === 'default'"
                                                    @click="openDeactivateModal(wallet)"
                                                    x-show="wallet.status === 'active' && wallet.balance > 0 && wallet.type !== 'default'"
                                                >
                                                    Desativar
                                                </button>
                                                <button 
                                                    class="btn btn-outline-success"
                                                    @click="if(confirm('Ativar esta carteira?')) document.getElementById(`activate-wallet-${wallet.id}`).submit()"
                                                    x-show="wallet.status === 'inactive'"
                                                >
                                                    Ativar
                                                </button>
                                                <form :id="`deactivate-wallet-${wallet.id}`" :action="`/wallets/${wallet.id}/status`" method="POST" class="d-none">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="inactive">
                                                </form>
                                                <form :id="`activate-wallet-${wallet.id}`" :action="`/wallets/${wallet.id}/status`" method="POST" class="d-none">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="active">
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                @if(count($wallets) === 0)
                                <tr>
                                    <td colspan="6" class="text-center py-3">Você não possui nenhuma carteira.</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h5 class="alert-heading">Sobre as carteiras:</h5>
                        <ul class="mb-0">
                            <li><strong>Carteira Principal:</strong> Criada automaticamente com sua conta e não pode ser desativada.</li>
                            <li><strong>Carteiras Secundárias:</strong> Você pode criar várias para organizar seu dinheiro por objetivos.</li>
                            <li><strong>Desativação:</strong> Para desativar uma carteira, transfira todo o saldo primeiro.</li>
                            <li><strong>Transferências:</strong> Você pode transferir dinheiro entre suas carteiras ou para outras contas.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Desativação com Transferência -->
<div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deactivateModalLabel">Desativar Carteira</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Para desativar a carteira <strong id="walletNameToDeactivate"></strong>, é necessário transferir o saldo de <strong id="walletBalanceToDeactivate"></strong> para outra carteira.</p>
                
                <form id="transferAndDeactivateForm" method="POST" action="">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="destination_wallet_id" class="form-label">Transferir saldo para:</label>
                        <select name="destination_wallet_id" id="destination_wallet_id" class="form-select" required>
                            <option value="">Selecione uma carteira</option>
                            <!-- Opções serão preenchidas via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Esta ação irá:
                        <ol class="mb-0">
                            <li>Transferir todo o saldo para a carteira selecionada</li>
                            <li>Desativar a carteira atual</li>
                        </ol>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('transferAndDeactivateForm').submit()">Transferir e Desativar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para abrir o modal de desativação
    function openDeactivateModal(wallet) {
        // Configurar o modal com os dados da carteira
        document.getElementById('walletNameToDeactivate').textContent = wallet.name;
        document.getElementById('walletBalanceToDeactivate').textContent = 'R$ ' + parseFloat(wallet.balance).toFixed(2).replace('.', ',');
        
        // Configurar o formulário
        const form = document.getElementById('transferAndDeactivateForm');
        form.action = `/wallets/${wallet.id}/deactivate-with-transfer`;
        
        // Limpar e preencher o select com as carteiras disponíveis
        const select = document.getElementById('destination_wallet_id');
        select.innerHTML = '<option value="">Selecione uma carteira</option>';
        
        // Preencher com todas as carteiras ativas, exceto a atual
        const wallets = JSON.parse('{{ json_encode($wallets) }}'.replace(/&quot;/g, '"'));
        
        wallets.forEach(function(w) {
            if (w.id !== wallet.id && w.status === 'active') {
                const option = document.createElement('option');
                option.value = w.id;
                option.textContent = `${w.name} (${w.account.number})`;
                select.appendChild(option);
            }
        });
        
        // Abrir o modal
        const deactivateModal = new bootstrap.Modal(document.getElementById('deactivateModal'));
        deactivateModal.show();
    }
</script>
@endsection