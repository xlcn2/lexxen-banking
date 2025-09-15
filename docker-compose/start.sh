#!/bin/bash

# Entrar no container da aplicação
docker-compose exec app bash -c "
    composer install
    php artisan key:generate
    php artisan migrate
    php artisan cache:clear
    php artisan config:clear
"

echo "Lexxen Banking está rodando! Acesse em http://localhost:8000"
