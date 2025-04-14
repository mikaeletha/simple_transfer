# API de Transferência Bancária

Esta API foi desenvolvida com Laravel 11 e tem como objetivo simular um sistema de transferência de dinheiro entre usuários. O projeto inclui regras específicas para diferentes tipos de usuários, validações com serviço externo, notificações e transações seguras.

---

## Funcionalidades

- Realiza transferências de dinheiro entre usuários
- Validação com serviço autorizador externo (mock)
- Notificação por e-mail ou SMS (mock)
- Suporte a dois tipos de usuários: **comuns** e **lojistas**
- Sistema de transações seguras (rollback em falhas)
- Seeders para popular o banco com dados de exemplo

---

## Tipos de usuários

- **Usuário comum**: pode enviar e receber dinheiro
- **Lojista**: só pode receber dinheiro (não envia)

---

## 📦 Tecnologias utilizadas

- PHP 8.3
- Laravel 11
- MySQL
- Composer
- Thunder Client / Postman (para testes)
- Mock APIs: [Autorização](https://util.devi.tools/api/v2/authorize) e [Notificação](https://util.devi.tools/api/v1/notify)

---

## Como rodar o projeto localmente

1. Clone o repositório:
```bash
git clone https://github.com/mikaeletha/simple_transfer
cd simple_transfer

2. Instale as dependências:
```bash
composer install

3. Copie o arquivo .env.example e configure:
```bash
cp .env.example .env

4. Gere a chave da aplicação:
```bash
php artisan key:generate

5. Configure as credenciais do banco de dados no .env

6. Rode as migrations e os seeders para popular o banco:
```bash
php artisan migrate --seed

7. Inicie o servidor local:

```bash
php artisan serve
A API estará disponível em: http://localhost:8000
