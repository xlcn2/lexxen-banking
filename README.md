# Lexxen Banking

Sistema completo de gestão financeira para usuários PF e PJ.

![Lexxen Banking Logo](docs/logo.png)

## Índice
1. [Sobre o Projeto](#sobre-o-projeto)
2. [Requisitos do Sistema](#requisitos-do-sistema)
3. [Instalação com Docker](#instalação-com-docker)
4. [Configuração Inicial](#configuração-inicial)
5. [Estrutura do Projeto](#estrutura-do-projeto)
6. [Funcionalidades](#funcionalidades)
7. [Guia de Testes](#guia-de-testes)
8. [Manutenção e Comandos Úteis](#manutenção-e-comandos-úteis)
9. [Resolução de Problemas](#resolução-de-problemas)
10. [Licença](#licença)

## Sobre o Projeto

Lexxen Banking é uma plataforma completa de gestão financeira desenvolvida em Laravel que permite usuários físicos e jurídicos gerenciarem suas contas, carteiras e realizarem transferências de forma segura e eficiente.

### Tecnologias Utilizadas

- **Back-end**: Laravel 10.x, PHP 8.1+
- **Front-end**: Bootstrap 5.x, Alpine.js
- **Banco de Dados**: PostgreSQL 15
- **Infraestrutura**: Docker, Redis

## Requisitos do Sistema

Para executar o projeto, você precisará:

- Git
- Docker 20.10+
- Docker Compose 2.0+


## Instalação com Docker

### 1. Clone o repositório

```bash
git clone https://github.com/xlcn2/lexxen-banking.git
cd banking
```

### 2. Configuração dos contêineres Docker

O projeto utiliza os seguintes contêineres:
- **app**: Aplicação Laravel (PHP-FPM)
- **db**: PostgreSQL 15
- **nginx**: Servidor web (Alpine)
- **redis**: Cache e filas (Alpine)

O arquivo `docker-compose.yml` está configurado da seguinte forma:

```yaml
version: '3'
services:
  app:
    build:
      args:
        user: lexxen
        uid: 1000
      context: ./
      dockerfile: Dockerfile
    image: lexxen-banking
    container_name: lexxen-app
    restart: unless-stopped
    working_dir: /var/www/
    volumes:
      - ./:/var/www
    networks:
      - lexxen-network

  db:
    image: postgres:15
    container_name: lexxen-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - lexxen-data:/var/lib/postgresql/data
    ports:
      - 5432:5432
    networks:
      - lexxen-network

  nginx:
    image: nginx:alpine
    container_name: lexxen-nginx
    restart: unless-stopped
    ports:
      - 8000:80
    volumes:
      - ./:/var/www
      - ./docker-compose/nginx:/etc/nginx/conf.d/
    networks:
      - lexxen-network

  redis:
    image: redis:alpine
    container_name: lexxen-redis
    restart: unless-stopped
    ports:
      - 6379:6379
    networks:
      - lexxen-network

networks:
  lexxen-network:
    driver: bridge

volumes:
  lexxen-data:
    driver: local
```

### 3. Variáveis de ambiente

Crie o arquivo de ambiente copiando o exemplo:

```bash
cp .env.example .env
```

Edite o arquivo `.env` para configurar as variáveis de ambiente, especialmente as relacionadas ao Docker:

```bash

# Ajuste as seguintes variáveis
APP_NAME="Lexxen Banking"
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=lexxen_banking
DB_USERNAME=lexxen
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 4. Iniciar os contêineres Docker

```bash
# Construa as imagens (apenas na primeira vez ou quando houver alterações no Dockerfile)
docker-compose build

# Inicie os contêineres em modo detached (background)
docker-compose up -d
```

Verifique se todos os contêineres estão rodando:

```bash
docker-compose ps
```

A saída deve mostrar que todos os serviços estão no estado "Up".

### 5. Instalação das dependências

```bash
# Instalar dependências do Composer
docker-compose exec app composer install

# Instalar dependências do Node.js e compilar assets
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 6. Configuração da aplicação Laravel

```bash
# Gerar chave da aplicação
docker-compose exec app php artisan key:generate

# Configurar permissões dos diretórios
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R lexxen:lexxen storage bootstrap/cache
```

### 7. Configuração do banco de dados

```bash
# Executar migrações para criar as tabelas
docker-compose exec app php artisan migrate

# Executar seeders para popular o banco de dados com dados iniciais
docker-compose exec app php artisan db:seed
```

Para executar apenas um seeder específico:

```bash
docker-compose exec app php artisan db:seed --class=UserSeeder
```

Para resetar o banco de dados e executar todas as migrações e seeders novamente:

```bash
docker-compose exec app php artisan migrate:fresh --seed
```

## Configuração Inicial

### Acessando a aplicação

Após a instalação, você pode acessar:

- **Aplicação**: http://localhost:8000

### Usuários e contas pré-configuradas

Após executar os seeders, os seguintes usuários estarão disponíveis:

| Tipo | Email | Senha | Status | Detalhes |
|------|-------|-------|--------|----------|
| PF | lucianopeas@gamil.com | senha123 | Aprovado | Possui 1 conta ativa com 2 carteiras |

0

## Estrutura do Projeto

```
lexxen-banking/
├── app/                    # Lógica principal da aplicação
│   ├── Http/               # Controllers e Middlewares
│   ├── Models/             # Modelos do banco de dados
│   ├── Observers/          # Observers para eventos de modelos
│   └── Providers/          # Service Providers
├── bootstrap/              # Arquivos de inicialização
├── config/                 # Configurações da aplicação
├── database/               # Migrações e Seeders
│   ├── migrations/         # Migrações do banco de dados
│   └── seeders/            # Classes de seeders
├── docker-compose/         # Arquivos de configuração Docker
│   └── nginx/              # Configuração do Nginx
├── public/                 # Arquivos públicos
├── resources/              # Views, assets e traduções
│   ├── js/                 # Arquivos JavaScript
│   ├── css/                # Arquivos CSS
│   └── views/              # Templates Blade
├── routes/                 # Definições de rotas
├── storage/                # Arquivos de armazenamento e logs
├── tests/                  # Testes automatizados
├── docker-compose.yml      # Configuração do Docker Compose
├── Dockerfile              # Instruções para construir a imagem Docker
└── .env.example            # Exemplo de variáveis de ambiente
```

## Funcionalidades

### 1. Usuários

- Cadastro e autenticação de pessoas físicas e jurídicas
- Perfis de usuário com informações detalhadas
- Sistema de aprovação para novos usuários
- Status de usuário (pendente, aprovado, bloqueado)

### 2. Contas

- Criação de contas bancárias com número único
- Status de conta (ativa/bloqueada)
- Saldo total calculado a partir das carteiras
- Somente usuários aprovados podem criar contas

### 3. Carteiras

- Múltiplas carteiras por conta
- Tipos de carteira (principal/secundária)
- Ativação/desativação de carteiras
- Transferência de fundos entre carteiras

### 4. Transferências

- Transferências entre carteiras do mesmo usuário
- Transferências entre usuários diferentes
- Histórico detalhado de transações
- Validações de segurança (saldo suficiente, status ativo)

### 5. Extratos

- Visualização detalhada de todas as transações
- Filtragem por período e carteira
- Registro automático após cada transferência
- Detalhes de saldo após cada operação


## Manutenção e Comandos Úteis

### Comandos Docker

```bash
# Ver status dos contêineres
docker-compose ps

# Ver logs dos contêineres
docker-compose logs

# Ver logs de um contêiner específico
docker-compose logs app

# Reiniciar todos os contêineres
docker-compose restart

# Reiniciar um contêiner específico
docker-compose restart app

# Parar todos os contêineres
docker-compose stop

# Parar e remover contêineres (mantém volumes)
docker-compose down

# Parar e remover contêineres e volumes (reset completo)
docker-compose down -v
```

### Comandos Laravel úteis

```bash
# Limpar caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear

# Executar migrações
docker-compose exec app php artisan migrate

# Reverter última migração
docker-compose exec app php artisan migrate:rollback

# Executar seeders
docker-compose exec app php artisan db:seed

# Abrir Tinker (REPL)
docker-compose exec app php artisan tinker

# Criar um novo controller
docker-compose exec app php artisan make:controller NomeController

# Criar um novo modelo com migração
docker-compose exec app php artisan make:model Nome -m
```

### Atualizando o projeto

```bash
# Atualizar código do repositório
git pull

# Atualizar dependências
docker-compose exec app composer update
docker-compose exec app npm update

# Reconstruir assets
docker-compose exec app npm run build

# Executar novas migrações
docker-compose exec app php artisan migrate
```

## Resolução de Problemas

### Problema: Contêineres não iniciam

**Verificação**: Execute `docker-compose ps` para ver o status.

**Solução**:
1. Verifique se as portas não estão em uso:
```bash
sudo lsof -i :8000
sudo lsof -i :5432
sudo lsof -i :6379
```

2. Verifique os logs:
```bash
docker-compose logs
```

3. Reinicie os serviços:
```bash
docker-compose down
docker-compose up -d
```

### Problema: Erro "Class Gate not found"

**Solução**: Verifique se o import correto está sendo usado em todos os controllers:

```php
// Correto:
use Illuminate\Support\Facades\Gate;

// Incorreto:
use App\Providers\Gate;
```

### Problema: Erros de permissão no storage

**Solução**:
```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R lexxen:lexxen storage bootstrap/cache
```

### Problema: Setas de paginação com tamanho incorreto

**Solução**: Verifique se o AppServiceProvider está configurado para usar Bootstrap:

```php
// Em app/Providers/AppServiceProvider.php
use Illuminate\Pagination\Paginator;

public function boot()
{
    Paginator::useBootstrap();
}
```

### Problema: Banco de dados corrompido

**Solução**: Reinicialize o banco de dados:
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

### Problema: Composer com erro de memória

**Solução**: Aumente o limite de memória do PHP:
```bash
docker-compose exec app php -d memory_limit=-1 /usr/local/bin/composer install
```

## Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

---

Desenvolvido por Luciano Filho | Última atualização: 2025-09-16