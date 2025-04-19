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

## Tecnologias utilizadas

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
cd simple_transfer/api
```
2. Instale as dependências:
```bash
composer install
```
3. Copie o arquivo .env.example e configure:
```bash
cp .env.example .env
```
4. Gere a chave da aplicação:
```bash
php artisan key:generate
```
5 . Crie a base de dados no MySQL:
```sql
CREATE DATABASE simple_transfer;
```
6. Configure as credenciais do banco de dados no .env

7. Rode as migrations e os seeders para popular o banco:
```bash
php artisan migrate --seed
```
8. Inicie o servidor local:
```bash
php artisan serve
```

## Endpoints

| Método | Rota          | Descrição                         |
|--------|---------------|-----------------------------------|
| POST   | /api/transfer | Realiza uma transferência         |
| GET    | /api/users    | Lista todos os usuários           |
| POST   | /api/user     | Cria usuário                      |

## Exemplo de requisição

### POST /api/transfer

```json
{
  "value": 100.0,
  "payer": 4,
  "payee": 15
}
```

### POST /api/user
```json
{
  "name": "Ana Paula",
  "cpf_cnpj": "12345678900",
  "email": "ana@example.com",
  "password": "senha123",
  "is_supplier": 0
}
```

## Testes
1. Criar tabelas 
```bash
php artisan migrate --env=testing
```

2. Popular com dados ficticios
```bash
php artisan db:seed --env=testing
```