# Fluxo Completo da Transferência

## 1. Requisição

- Requisição recebida via `POST /transfer`

---

## 2. Validação Inicial

- Validação feita pelo `TransferRequest`:
  - Campos obrigatórios
  - Formatos corretos
  - Regras básicas (ex: não transferir para si mesmo)

---

## 3. Lógica no Controller

- O Controller aciona o `TransferService`

---

## 4. TransferService

### Regras de Negócio Validadas

- Verifica se o pagador tem **saldo suficiente**
- Garante que os **tipos de usuários** são válidos (usuário pode enviar, lojista não)
- Confirma que o **remetente é diferente do destinatário**

### Consulta ao Serviço Externo

- Chamada para o serviço de autorização externo
  - `GET https://util.devi.tools/api/v2/authorize`
- Se **não autorizado** → lança exceção e **interrompe** a transferência

### Execução da Transação

- **Inicia uma transação de banco de dados**
- Debita o saldo da conta do **remetente**
- Credita o saldo da conta do **destinatário**
- Registra os dados da transação em tabela (`transactions`)

### Notificação

- Tenta enviar uma notificação ao recebedor
  - `POST https://util.devi.tools/api/v1/notify`
- Se falhar → **registra o erro**, mas **não cancela** a transferência

---

## 5. Resposta ao Usuário

- Se tudo correr bem → retorna **resposta de sucesso**
- Se ocorrer erro de regra de negócio ou autorização → retorna **erro apropriado**
