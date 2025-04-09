-- Criar base de dados
CREATE DATABASE simple_transfer;

-- Gerar key do .env
php artisan key:generate


-- limpa e recria o banco:
php artisan migrate:fresh --seed
