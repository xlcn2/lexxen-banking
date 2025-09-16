@extends('layouts.app')

@section('title', 'Nova Transferência')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Nova Transferência</div>

                <div class="card-body">
                    <div class="mb-4">
                        <div class="btn-group w-100" id="transferTypeButtons">
                            <button type="button" class="btn btn-primary" id="internalBtn" onclick="setTransferType('internal')">
                                Entre minhas carteiras
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="externalBtn" onclick="setTransferType('external')">
                                Para outra conta
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('transfers.store') }}" method="POST" id="transferForm">
                        @csrf
                        
                        <input type="hidden" name="transfer_type" id="transferType" value="internal">
                        
                        <div class="mb-3">
                            <label for="source_wallet_id" class="form-label">Carteira de Origem</label>
                            <select 
                                name="source_wallet_id" 
                                id="source_wallet_id" 
                                class="form-select @error('source_wallet_id') is-invalid @enderror"
                                required
                                onchange="updateSourceBalance()"
                            >
                                <option value="">Selecione uma carteira</option>
                                @foreach($wallets as $wallet)
                                    <option 
                                        value="{{ $wallet['id'] }}" 
                                        data-balance="{{ $wallet['balance'] }}"
                                        {{ $sourceWalletId == $wallet['id'] ? 'selected' : '' }}
                                    >
                                        {{ $wallet['name'] }} (R$ {{ number_format($wallet['balance'], 2, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('source_wallet_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3" id="balanceInfo" style="display: none;">
                            <div class="alert alert-success">
                                <strong>Saldo disponível:</strong> R$ <span id="availableBalance">0,00</span>
                            </div>
                        </div>
                        
                        <!-- Destino para transferências internas -->
                        <div class="mb-3" id="internalDestination">
                            <label for="internal_destination_wallet_id" class="form-label">Carteira de Destino</label>
                            <select 
                                id="internal_destination_wallet_id" 
                                class="form-select @error('destination_wallet_id') is-invalid @enderror"
                                onchange="updateDestinationField(this.value)"
                            >
                                <option value="">Selecione uma carteira</option>
                                @foreach($wallets as $wallet)
                                    <option value="{{ $wallet['id'] }}">
                                        {{ $wallet['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Destino para transferências externas -->
                        <div class="mb-3" id="externalDestination" style="display: none;">
                            <label for="external_destination_wallet_id" class="form-label">Conta de Destino</label>
                            <select 
                                id="external_destination_wallet_id" 
                                class="form-select @error('destination_wallet_id') is-invalid @enderror"
                                onchange="updateDestinationField(this.value)"
                            >
                                <option value="">Selecione uma conta de destino</option>
                                @foreach($otherWallets as $wallet)
                                    <option value="{{ $wallet['id'] }}">
                                        {{ $wallet['account_number'] }} ({{ $wallet['user_name'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Campo oculto que será enviado no formulário -->
                        <input type="hidden" name="destination_wallet_id" id="destination_wallet_id" value="">
                        
                        @error('destination_wallet_id')
                            <div class="text-danger mb-3">{{ $message }}</div>
                        @enderror
                        
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
                                    required
                                >
                            </div>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <h5 class="alert-heading">Informações importantes:</h5>
                            <ul class="mb-0">
                                <li>Transferências entre suas próprias carteiras são processadas imediatamente.</li>
                                <li>Transferências para outras contas serão direcionadas para a carteira principal do destinatário.</li>
                                <li>O saldo disponível para transferência é exibido ao lado do nome da carteira.</li>
                                <li>Não é possível transferir de uma carteira bloqueada ou para uma carteira inativa.</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Transferir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para alternar tipo de transferência
    function setTransferType(type) {
        document.getElementById('transferType').value = type;
        
        if (type === 'internal') {
            document.getElementById('internalBtn').classList.replace('btn-outline-primary', 'btn-primary');
            document.getElementById('externalBtn').classList.replace('btn-primary', 'btn-outline-primary');
            document.getElementById('internalDestination').style.display = 'block';
            document.getElementById('externalDestination').style.display = 'none';
            document.getElementById('destination_wallet_id').value = '';
        } else {
            document.getElementById('externalBtn').classList.replace('btn-outline-primary', 'btn-primary');
            document.getElementById('internalBtn').classList.replace('btn-primary', 'btn-outline-primary');
            document.getElementById('externalDestination').style.display = 'block';
            document.getElementById('internalDestination').style.display = 'none';
            document.getElementById('destination_wallet_id').value = '';
        }
    }
    
    // Função para atualizar o campo oculto com o valor selecionado
    function updateDestinationField(value) {
        document.getElementById('destination_wallet_id').value = value;
    }
    
    // Função para atualizar o saldo exibido
    function updateSourceBalance() {
        const select = document.getElementById('source_wallet_id');
        const balanceInfo = document.getElementById('balanceInfo');
        const balanceDisplay = document.getElementById('availableBalance');
        
        if (select.value) {
            const option = select.options[select.selectedIndex];
            const balance = option.getAttribute('data-balance');
            
            // Formatando o número
            const formatter = new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            balanceDisplay.textContent = formatter.format(balance);
            balanceInfo.style.display = 'block';
            
            // Desabilitar a opção de selecionar a mesma carteira como destino
            const internalSelect = document.getElementById('internal_destination_wallet_id');
            for (let i = 0; i < internalSelect.options.length; i++) {
                internalSelect.options[i].disabled = (internalSelect.options[i].value === select.value);
            }
        } else {
            balanceInfo.style.display = 'none';
        }
    }
    
    // Validação de formulário antes do envio
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        const destinationValue = document.getElementById('destination_wallet_id').value;
        
        if (!destinationValue) {
            e.preventDefault();
            alert('Por favor, selecione uma carteira de destino.');
            return false;
        }
        
        // Validar se a carteira de origem é diferente da de destino
        const sourceValue = document.getElementById('source_wallet_id').value;
        if (sourceValue === destinationValue) {
            e.preventDefault();
            alert('A carteira de origem e destino não podem ser iguais.');
            return false;
        }
        
        return true;
    });
    
    // Inicializar o formulário
    window.onload = function() {
        // Pré-selecionar carteira de origem se especificada
        const sourceSelect = document.getElementById('source_wallet_id');
        if (sourceSelect.value) {
            updateSourceBalance();
        }
    };
</script>
@endsection