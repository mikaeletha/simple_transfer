# API de Transferência Bancária (Laravel 11)

Esta API simula um sistema de transferência de dinheiro entre usuários, implementada com Laravel 11. O sistema incorpora regras de negócio específicas para diferentes tipos de usuários, validação com um serviço externo simulado, notificações (também simuladas) e transações seguras.

---

## Visão Geral das Funcionalidades

* **Transferências:** Permite a transferência de fundos entre usuários cadastrados.
* **Validação Externa:** Integração com um serviço de autorização externo (mock) para validar transações.
* **Notificações:** Envio de notificações (e-mail ou SMS, ambos mockados) após eventos relevantes.
* **Tipos de Usuário:** Suporte a dois perfis de usuário com permissões distintas:
    * **Comum:** Pode enviar e receber dinheiro.
    * **Fornecedor:** Pode apenas receber dinheiro, não enviar.
* **Transações Seguras:** Implementação de transações com rollback automático em caso de falha, garantindo a integridade dos dados.
* **Dados de Exemplo:** Seeders incluídos para popular o banco de dados com dados de teste.

---

## Tipos de Usuário em Detalhe

* **Usuário Comum:** Capaz de iniciar e receber transferências de dinheiro.
* **Fornecedor:** Restrito a receber transferências, não podendo enviar fundos para outros usuários.

---

## Tecnologias Utilizadas

* **Linguagem:** PHP 8.3
* **Framework:** Laravel 11
* **Banco de Dados:** SQLite
* **Gerenciador de Dependências:** Composer
* **Ferramentas de Teste API:** Thunder Client ou Postman (recomendado para interagir com a API)
* **APIs Mockadas:**
    * **Autorização:** [https://util.devi.tools/api/v2/authorize](https://util.devi.tools/api/v2/authorize) (Simulação de serviço externo)
    * **Notificação:** [https://util.devi.tools/api/v1/notify](https://util.devi.tools/api/v1/notify) (Simulação de envio de notificações)

---

## Requisitos para Execução Local

Antes de prosseguir com a execução local, certifique-se de ter as seguintes ferramentas instaladas em sua máquina:

* [Git](https://git-scm.com/)
* [Docker](https://www.docker.com/)
* [WSL2 (Windows Subsystem for Linux 2)](https://learn.microsoft.com/pt-br/windows/wsl/install) (Necessário apenas em ambiente Windows)

---

## Guia de Execução Local

Siga estes passos para rodar o projeto em seu ambiente local:

1.  **Clonar o Repositório:**
    ```bash
    git clone [https://github.com/mikaeletha/simple_transfer](https://github.com/mikaeletha/simple_transfer)
    cd simple_transfer/api
    ```

2.  **Configurar o Arquivo de Ambiente (.env):**
    Copie o arquivo de exemplo `.env.example` para `.env` e configure as variáveis de ambiente conforme necessário.
    ```bash
    cp .env.example .env
    ```

3.  **Iniciar os Contêineres Docker:**
    Utilize o Docker Compose para construir e iniciar os contêineres definidos no projeto.
    ```bash
    docker-compose up -d --build
    ```

4.  **Instalar as Dependências do Laravel (Dentro do Contêiner):**
    Acesse o contêiner da aplicação para executar o Composer.
    ```bash
    docker exec -it simple_transfer_app composer install
    ```

5.  **Gerar a Chave da Aplicação Laravel:**
    Gere uma chave única para a sua aplicação Laravel dentro do contêiner.
    ```bash
    docker exec -it simple_transfer_app php artisan key:generate
    ```

6.  **Executar as Migrações do Banco de Dados:**
    Execute as migrations do Laravel para criar as tabelas no banco de dados SQLite.
    ```bash
    docker exec -it simple_transfer_app php artisan migrate
    ```

---

## Configuração do SQLite

Este projeto já está configurado para utilizar o banco de dados SQLite. O arquivo do banco de dados será automaticamente criado (se não existir) no seguinte caminho dentro do contêiner:
Caso necessite criar o arquivo manualmente (por exemplo, se houver problemas de permissão), utilize o seguinte comando **dentro do contêiner**:

```bash
touch database/database.sqlite
```

---

## Acessando a API
Após a execução dos passos anteriores, a API estará acessível através da seguinte URL no seu navegador ou ferramenta de teste API:
http://localhost:8000
Utilize ferramentas como Thunder Client (extensão VS Code) ou Postman para interagir com os endpoints da API.

---

## Endpoints

| Método | Rota          | Descrição                         |
|--------|---------------|-----------------------------------|
| POST   | /api/transfer | Realiza uma transferência         |
| GET    | /api/users    | Lista todos os usuários           |
| POST   | /api/user     | Cria usuário                      |

---

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

---

## Execução dos Testes
Para executar os testes automatizados da API, siga estes passos dentro do contêiner:
Acessar o Shell do Contêiner:
```bash
docker exec -it simple_transfer_app sh
```
Executar os Testes com Composer:
```bash
composer run-test
```