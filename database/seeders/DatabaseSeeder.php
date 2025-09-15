<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IndividualUser;
use App\Models\CorporateUser;
use App\Models\Account;
use App\Models\Wallet;
use App\Models\Transfer;
use App\Models\Statement;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "Iniciando seed para cenários de teste Lexxen...\n";
        
        // ----------------------------------------
        // CONTAS PRINCIPAIS PARA LOGIN - FÁCIL ACESSO
        // ----------------------------------------
        
        echo "Criando contas principais para login fácil\n";
        
        // Sua conta principal
        $mainUser = IndividualUser::updateOrCreate(
            ['email' => 'lucianopeas@gmail.com'],
            [
                'name' => 'Luciano Peas',
                'password' => Hash::make('senha123'),
                'cpf' => '98765432100',
                'status' => 'approved',
            ]
        );
        
        // Verificar se já tem conta
        $mainAccount = Account::firstOrCreate(
            [
                'accountable_id' => $mainUser->id,
                'accountable_type' => get_class($mainUser),
            ],
            [
                'number' => '10000001',
                'status' => 'active',
            ]
        );
        
        // Verificar se já tem carteira principal
        $mainWallet = Wallet::firstOrCreate(
            [
                'account_id' => $mainAccount->id,
                'type' => 'default',
            ],
            [
                'name' => 'Carteira Principal',
                'balance' => 10000.00,
                'status' => 'active',
            ]
        );
        
        // Verificar se já tem carteira secundária
        $secondaryWallet = Wallet::firstOrCreate(
            [
                'account_id' => $mainAccount->id,
                'name' => 'Carteira Investimentos',
            ],
            [
                'balance' => 5000.00,
                'type' => 'wallet',
                'status' => 'active',
            ]
        );
        
        // Conta de Teste 1 - Fácil de lembrar
        $testUser1 = IndividualUser::updateOrCreate(
            ['email' => 'teste@teste.com'],
            [
                'name' => 'Usuário Teste',
                'password' => Hash::make('teste123'),
                'cpf' => '12345678900',
                'status' => 'approved',
            ]
        );
        
        $testAccount1 = Account::firstOrCreate(
            [
                'accountable_id' => $testUser1->id,
                'accountable_type' => get_class($testUser1),
            ],
            [
                'number' => '20000001',
                'status' => 'active',
            ]
        );
        
        $testWallet1 = Wallet::firstOrCreate(
            [
                'account_id' => $testAccount1->id,
                'type' => 'default',
            ],
            [
                'name' => 'Carteira Principal',
                'balance' => 8000.00,
                'status' => 'active',
            ]
        );
        
        // Carteira secundária para teste1
        $testWallet1Secondary = Wallet::firstOrCreate(
            [
                'account_id' => $testAccount1->id,
                'name' => 'Carteira Secundária',
            ],
            [
                'balance' => 2000.00,
                'type' => 'wallet',
                'status' => 'active',
            ]
        );
        
        // Conta de Teste 2 - Fácil de lembrar
        $testUser2 = IndividualUser::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'cpf' => '11122233344',
                'status' => 'approved',
            ]
        );
        
        $testAccount2 = Account::firstOrCreate(
            [
                'accountable_id' => $testUser2->id,
                'accountable_type' => get_class($testUser2),
            ],
            [
                'number' => '20000002',
                'status' => 'active',
            ]
        );
        
        $testWallet2 = Wallet::firstOrCreate(
            [
                'account_id' => $testAccount2->id,
                'type' => 'default',
            ],
            [
                'name' => 'Carteira Principal',
                'balance' => 15000.00,
                'status' => 'active',
            ]
        );
        
        // Empresa com credenciais fáceis
        $testCompany = CorporateUser::updateOrCreate(
            ['email' => 'empresa@empresa.com'],
            [
                'company_name' => 'Empresa Fácil LTDA',
                'trading_name' => 'Empresa Fácil',
                'password' => Hash::make('empresa123'),
                'cnpj' => '00123456000199',
                'status' => 'approved',
            ]
        );
        
        $companyEasyAccount = Account::firstOrCreate(
            [
                'accountable_id' => $testCompany->id,
                'accountable_type' => get_class($testCompany),
            ],
            [
                'number' => '30000001',
                'status' => 'active',
            ]
        );
        
        $companyWallet = Wallet::firstOrCreate(
            [
                'account_id' => $companyEasyAccount->id,
                'type' => 'default',
            ],
            [
                'name' => 'Carteira Empresarial',
                'balance' => 25000.00,
                'status' => 'active',
            ]
        );
        
        // ----------------------------------------
        // CRIAR TRANSFERÊNCIAS ENTRE CONTAS PRINCIPAIS
        // ----------------------------------------
        
        echo "Criando transferências entre contas principais...\n";
        
        // Arrays para armazenar transferências
        $transfers = [];
        $wallets = [
            $mainWallet, 
            $secondaryWallet, 
            $testWallet1, 
            $testWallet1Secondary, 
            $testWallet2, 
            $companyWallet
        ];
        
        // Função para criar transferência
        $createTransfer = function($source, $destination, $amount, $daysAgo = 0) {
            $status = 'completed';
            $date = Carbon::now()->subDays($daysAgo);
            
            // Verificar se já existe uma transferência idêntica
            $existingTransfer = Transfer::where('source_wallet_id', $source->id)
                ->where('destination_wallet_id', $destination->id)
                ->where('amount', $amount)
                ->where('created_at', $date)
                ->first();
                
            if ($existingTransfer) {
                echo "Transferência já existente: {$source->name} -> {$destination->name} = R$ {$amount}\n";
                return $existingTransfer;
            }
            
            // Criar a transferência
            $transfer = new Transfer();
            $transfer->source_wallet_id = $source->id;
            $transfer->destination_wallet_id = $destination->id;
            $transfer->amount = $amount;
            $transfer->status = $status;
            $transfer->idempotency_key = Str::uuid();
            $transfer->created_at = $date;
            $transfer->updated_at = $date;
            $transfer->save();
            
            // Criar statements
            Statement::create([
                'wallet_id' => $source->id,
                'transfer_id' => $transfer->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $source->balance - $amount,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            
            Statement::create([
                'wallet_id' => $destination->id,
                'transfer_id' => $transfer->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $destination->balance + $amount,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            
            echo "Transferência criada: {$source->name} -> {$destination->name} = R$ {$amount}\n";
            return $transfer;
        };
        
        // Transferências entre suas carteiras
        try {
            // Entre suas próprias carteiras
            $transfers[] = $createTransfer($mainWallet, $secondaryWallet, 500.00, 10);
            $transfers[] = $createTransfer($secondaryWallet, $mainWallet, 200.00, 8);
            
            // Da sua conta para teste1
            $transfers[] = $createTransfer($mainWallet, $testWallet1, 1500.00, 7);
            $transfers[] = $createTransfer($testWallet1, $mainWallet, 300.00, 5);
            
            // Da sua conta para teste2
            $transfers[] = $createTransfer($mainWallet, $testWallet2, 2000.00, 4);
            $transfers[] = $createTransfer($testWallet2, $mainWallet, 500.00, 3);
            
            // Da sua conta para empresa
            $transfers[] = $createTransfer($mainWallet, $companyWallet, 3000.00, 2);
            $transfers[] = $createTransfer($companyWallet, $mainWallet, 1200.00, 1);
            
            // Entre contas de teste
            $transfers[] = $createTransfer($testWallet1, $testWallet2, 400.00, 6);
            $transfers[] = $createTransfer($testWallet2, $testWallet1, 250.00, 4);
            
            // Entre carteiras do mesmo usuário
            $transfers[] = $createTransfer($testWallet1, $testWallet1Secondary, 300.00, 3);
            $transfers[] = $createTransfer($testWallet1Secondary, $testWallet1, 100.00, 2);
            
            // Transferências da empresa
            $transfers[] = $createTransfer($companyWallet, $testWallet1, 800.00, 5);
            $transfers[] = $createTransfer($companyWallet, $testWallet2, 1200.00, 3);
            
            // Transferências mais recentes (hoje e ontem)
            $transfers[] = $createTransfer($mainWallet, $testWallet1, 350.00, 0);
            $transfers[] = $createTransfer($testWallet2, $mainWallet, 420.00, 0);
            $transfers[] = $createTransfer($companyWallet, $mainWallet, 1500.00, 0);
        } catch (\Exception $e) {
            echo "Erro ao criar transferências: " . $e->getMessage() . "\n";
        }
        
        // ----------------------------------------
        // CRIAR TRANSFERÊNCIAS E EXTRATOS ADICIONAIS PARA TESTES
        // ----------------------------------------
        
        echo "Criando histórico de transferências adicionais...\n";
        
        // Criar mais transferências aleatórias para histórico rico
        try {
            // Criar 50 transferências aleatórias entre contas nos últimos 30 dias
            for ($i = 1; $i <= 50; $i++) {
                $sourceIndex = array_rand($wallets);
                $destinationIndex = array_rand($wallets);
                
                // Evitar transferências para a mesma carteira
                while ($sourceIndex == $destinationIndex) {
                    $destinationIndex = array_rand($wallets);
                }
                
                $source = $wallets[$sourceIndex];
                $destination = $wallets[$destinationIndex];
                
                // Valor aleatório entre 10 e 1000
                $amount = rand(10, 1000);
                
                // Data aleatória nos últimos 30 dias
                $daysAgo = rand(0, 30);
                
                $transfers[] = $createTransfer($source, $destination, $amount, $daysAgo);
            }
        } catch (\Exception $e) {
            echo "Erro ao criar transferências aleatórias: " . $e->getMessage() . "\n";
        }
        
        // ----------------------------------------
        // Cenário 1: Aprovação de Usuários
        // ----------------------------------------
        
        echo "Preparando Cenário 1: Aprovação de Usuários\n";
        
        // Criar 10 usuários pendentes para o teste de aprovação
        for ($i = 1; $i <= 10; $i++) {
            IndividualUser::firstOrCreate(
                ['email' => "pending{$i}@lexxen.test"],
                [
                    'name' => "Usuário Pendente {$i}",
                    'password' => Hash::make('password'),
                    'cpf' => "1111111111{$i}",
                    'status' => 'pending_approval'
                ]
            );
        }
        
        // ----------------------------------------
        // Cenário 2: Transferência Concorrente
        // ----------------------------------------
        
        echo "Preparando Cenário 2: Transferência Concorrente\n";
        
        // Criar dois usuários para teste de concorrência
        $userA = IndividualUser::firstOrCreate(
            ['email' => 'usera@lexxen.test'],
            [
                'name' => 'Usuário A',
                'password' => Hash::make('password'),
                'cpf' => '22222222222',
                'status' => 'approved'
            ]
        );
        
        $userB = IndividualUser::firstOrCreate(
            ['email' => 'userb@lexxen.test'],
            [
                'name' => 'Usuário B',
                'password' => Hash::make('password'),
                'cpf' => '33333333333',
                'status' => 'approved'
            ]
        );
        
        // Criar contas para teste de concorrência
        $accountA = Account::firstOrCreate(
            [
                'accountable_id' => $userA->id,
                'accountable_type' => get_class($userA)
            ],
            [
                'number' => '40000001',
                'status' => 'active'
            ]
        );
        
        $accountB = Account::firstOrCreate(
            [
                'accountable_id' => $userB->id,
                'accountable_type' => get_class($userB)
            ],
            [
                'number' => '40000002',
                'status' => 'active'
            ]
        );
        
        // Criar carteiras DEFAULT com saldo 1000
        $walletA = Wallet::firstOrCreate(
            [
                'account_id' => $accountA->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira A',
                'balance' => 1000.00,
                'status' => 'active'
            ]
        );
        
        $walletB = Wallet::firstOrCreate(
            [
                'account_id' => $accountB->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira B',
                'balance' => 1000.00,
                'status' => 'active'
            ]
        );
        
        // ----------------------------------------
        // Cenário 3: Transferência Duplicada (Idempotência)
        // ----------------------------------------
        
        echo "Preparando Cenário 3: Transferência Duplicada (Idempotência)\n";
        
        // Criar usuários para teste de idempotência
        $userC = IndividualUser::firstOrCreate(
            ['email' => 'userc@lexxen.test'],
            [
                'name' => 'Usuário C',
                'password' => Hash::make('password'),
                'cpf' => '44444444444',
                'status' => 'approved'
            ]
        );
        
        $userD = IndividualUser::firstOrCreate(
            ['email' => 'userd@lexxen.test'],
            [
                'name' => 'Usuário D',
                'password' => Hash::make('password'),
                'cpf' => '55555555555',
                'status' => 'approved'
            ]
        );
        
        // Criar contas para teste de idempotência
        $accountC = Account::firstOrCreate(
            [
                'accountable_id' => $userC->id,
                'accountable_type' => get_class($userC)
            ],
            [
                'number' => '50000001',
                'status' => 'active'
            ]
        );
        
        $accountD = Account::firstOrCreate(
            [
                'accountable_id' => $userD->id,
                'accountable_type' => get_class($userD)
            ],
            [
                'number' => '50000002',
                'status' => 'active'
            ]
        );
        
        // Criar carteiras para teste de idempotência
        $walletC = Wallet::firstOrCreate(
            [
                'account_id' => $accountC->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira C',
                'balance' => 1000.00,
                'status' => 'active'
            ]
        );
        
        $walletD = Wallet::firstOrCreate(
            [
                'account_id' => $accountD->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira D',
                'balance' => 1000.00,
                'status' => 'active'
            ]
        );
        
        // ----------------------------------------
        // Cenário 4: Conta Bloqueada
        // ----------------------------------------
        
        echo "Preparando Cenário 4: Conta Bloqueada\n";
        
        // Criar usuário com conta bloqueada
        $blockedUser = IndividualUser::firstOrCreate(
            ['email' => 'blocked@lexxen.test'],
            [
                'name' => 'Usuário Bloqueado',
                'password' => Hash::make('password'),
                'cpf' => '66666666666',
                'status' => 'approved'
            ]
        );
        
        // Criar conta bloqueada
        $blockedAccount = Account::firstOrCreate(
            [
                'accountable_id' => $blockedUser->id,
                'accountable_type' => get_class($blockedUser)
            ],
            [
                'number' => '60000001',
                'status' => 'blocked'
            ]
        );
        
        // Criar carteira para conta bloqueada
        $blockedWallet = Wallet::firstOrCreate(
            [
                'account_id' => $blockedAccount->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira Bloqueada',
                'balance' => 500.00,
                'status' => 'active'
            ]
        );
        
        // ----------------------------------------
        // Cenário 5: Extrato Paginado e Filtrado
        // ----------------------------------------
        
        echo "Preparando Cenário 5: Extrato Paginado e Filtrado\n";
        
        // Criar usuários para teste de extrato
        $userE = IndividualUser::firstOrCreate(
            ['email' => 'usere@lexxen.test'],
            [
                'name' => 'Usuário E',
                'password' => Hash::make('password'),
                'cpf' => '77777777777',
                'status' => 'approved'
            ]
        );
        
        $userF = IndividualUser::firstOrCreate(
            ['email' => 'userf@lexxen.test'],
            [
                'name' => 'Usuário F',
                'password' => Hash::make('password'),
                'cpf' => '88888888888',
                'status' => 'approved'
            ]
        );
        
        // Criar contas para teste de extrato
        $accountE = Account::firstOrCreate(
            [
                'accountable_id' => $userE->id,
                'accountable_type' => get_class($userE)
            ],
            [
                'number' => '70000001',
                'status' => 'active'
            ]
        );
        
        $accountF = Account::firstOrCreate(
            [
                'accountable_id' => $userF->id,
                'accountable_type' => get_class($userF)
            ],
            [
                'number' => '70000002',
                'status' => 'active'
            ]
        );
        
        // Criar carteiras para teste de extrato
        $walletE = Wallet::firstOrCreate(
            [
                'account_id' => $accountE->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira E',
                'balance' => 5000.00,
                'status' => 'active'
            ]
        );
        
        $walletF = Wallet::firstOrCreate(
            [
                'account_id' => $accountF->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira F',
                'balance' => 5000.00,
                'status' => 'active'
            ]
        );
        
        // Criar 50 transferências entre as carteiras E e F em datas diferentes
        try {
            for ($i = 0; $i < 50; $i++) {
                $isEven = $i % 2 === 0;
                $source = $isEven ? $walletE : $walletF;
                $destination = $isEven ? $walletF : $walletE;
                $amount = rand(50, 500);
                $daysAgo = 30 - $i * 0.6; // Distribui ao longo de 30 dias
                
                $createTransfer($source, $destination, $amount, $daysAgo);
            }
        } catch (\Exception $e) {
            echo "Erro ao criar transferências para teste de extrato: " . $e->getMessage() . "\n";
        }
        
        // ----------------------------------------
        // Cenário 6: Performance (N+1 Queries)
        // ----------------------------------------
        
        echo "Preparando Cenário 6: Performance (N+1 Queries)\n";
        
        // Criar 5 usuários para teste de N+1
        for ($i = 1; $i <= 5; $i++) {
            $performanceUser = IndividualUser::firstOrCreate(
                ['email' => "performance{$i}@lexxen.test"],
                [
                    'name' => "Usuário Performance {$i}",
                    'password' => Hash::make('password'),
                    'cpf' => "9999999999{$i}",
                    'status' => 'approved'
                ]
            );
            
            // Criar 5 contas para cada usuário
            for ($j = 1; $j <= 5; $j++) {
                $performanceAccount = Account::firstOrCreate(
                    [
                        'accountable_id' => $performanceUser->id,
                        'accountable_type' => get_class($performanceUser),
                        'number' => "8{$i}0000{$j}"
                    ],
                    [
                        'status' => 'active'
                    ]
                );
                
                // Criar carteira DEFAULT para cada conta
                $performanceWallet = Wallet::firstOrCreate(
                    [
                        'account_id' => $performanceAccount->id,
                        'type' => 'default'
                    ],
                    [
                        'name' => "Carteira {$i}-{$j}",
                        'balance' => 1000.00,
                        'status' => 'active'
                    ]
                );
                
                // Criar algumas transferências para cada conta
                try {
                    for ($k = 0; $k < 10; $k++) {
                        // Selecionar outra carteira para transferência
                        $otherAccountIndex = rand(1, 5);
                        $otherWalletIndex = rand(1, 5);
                        
                        // Evitar transferência para a mesma carteira
                        while ($otherAccountIndex == $i && $otherWalletIndex == $j) {
                            $otherAccountIndex = rand(1, 5);
                            $otherWalletIndex = rand(1, 5);
                        }
                        
                        $otherAccount = Account::where('number', "8{$otherAccountIndex}0000{$otherWalletIndex}")->first();
                        if ($otherAccount) {
                            $otherWallet = Wallet::where('account_id', $otherAccount->id)
                                ->where('type', 'default')
                                ->first();
                                
                            if ($otherWallet) {
                                $amount = rand(10, 200);
                                $daysAgo = rand(0, 30);
                                
                                $createTransfer($performanceWallet, $otherWallet, $amount, $daysAgo);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    echo "Erro ao criar transferências para N+1: " . $e->getMessage() . "\n";
                }
            }
        }
        
        // ----------------------------------------
        // Cenário 7: Cache de Saldo
        // ----------------------------------------
        
        echo "Preparando Cenário 7: Cache de Saldo\n";
        
        // Criar usuário para teste de cache
        $cacheUser = IndividualUser::firstOrCreate(
            ['email' => 'cache@lexxen.test'],
            [
                'name' => 'Usuário Cache',
                'password' => Hash::make('password'),
                'cpf' => '10101010101',
                'status' => 'approved'
            ]
        );
        
        // Criar conta para teste de cache
        $cacheAccount = Account::firstOrCreate(
            [
                'accountable_id' => $cacheUser->id,
                'accountable_type' => get_class($cacheUser)
            ],
            [
                'number' => '90000001',
                'status' => 'active'
            ]
        );
        
        // Criar carteira para teste de cache
        $cacheWallet = Wallet::firstOrCreate(
            [
                'account_id' => $cacheAccount->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira Cache',
                'balance' => 1000.00,
                'status' => 'active'
            ]
        );
        
        // ----------------------------------------
        // Cenário 8: Job de Aprovação em Massa com Erro
        // ----------------------------------------
        
        echo "Preparando Cenário 8: Job de Aprovação em Massa com Erro\n";
        
        // Criar 20 usuários para teste de aprovação em massa
        for ($i = 1; $i <= 20; $i++) {
            IndividualUser::firstOrCreate(
                ['email' => "approval{$i}@lexxen.test"],
                [
                    'name' => "Usuário Aprovação {$i}",
                    'password' => Hash::make('password'),
                    'cpf' => "2020202020{$i}",
                    'status' => 'pending_approval'
                ]
            );
        }
        
        // ----------------------------------------
        // Usuário Corporativo (Empresa)
        // ----------------------------------------
        
        echo "Criando usuário corporativo (empresa)\n";
        
        // Criar uma empresa aprovada
        $company = CorporateUser::firstOrCreate(
            ['email' => 'empresa@lexxen.test'],
            [
                'company_name' => 'Empresa Teste LTDA',
                'password' => Hash::make('password'),
                'cnpj' => '12345678000190',
                'status' => 'approved',
            ]
        );
        
        // Criar conta para a empresa
        $companyAccount = Account::firstOrCreate(
            [
                'accountable_id' => $company->id,
                'accountable_type' => get_class($company)
            ],
            [
                'number' => '11110000',
                'status' => 'active'
            ]
        );
        
        // Criar carteira para a empresa
        $companyTestWallet = Wallet::firstOrCreate(
            [
                'account_id' => $companyAccount->id,
                'type' => 'default'
            ],
            [
                'name' => 'Carteira Empresarial',
                'balance' => 50000.00,
                'status' => 'active'
            ]
        );
        
        echo "\nSeed concluído com sucesso!\n";
        echo "Usuários criados: " . IndividualUser::count() . " individuais, " . CorporateUser::count() . " empresas\n";
        echo "Contas: " . Account::count() . "\n";
        echo "Carteiras: " . Wallet::count() . "\n";
        echo "Transferências: " . Transfer::count() . "\n";
        echo "Statements: " . Statement::count() . "\n";
        
        echo "\n\n";
        echo "=========================================================\n";
        echo "CONTAS DISPONÍVEIS PARA LOGIN:\n";
        echo "=========================================================\n";
        echo "1. Email: lucianopeas@gmail.com | Senha: senha123\n";
        echo "2. Email: teste@teste.com | Senha: teste123\n";
        echo "3. Email: admin@admin.com | Senha: admin123\n";
        echo "4. Email: empresa@empresa.com | Senha: empresa123 (Empresa)\n";
        echo "=========================================================\n";
    }
}