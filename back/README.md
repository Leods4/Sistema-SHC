# Documentação da API - SHC (Sistema de Horas Complementares) - v1.1

### 1. VISÃO GERAL

Base URL:                http://localhost:8000/api
Framework:               Laravel 12
Autenticação:            Bearer Token (Laravel Sanctum)
Formato de Resposta:     JSON (application/json)

---
### 2. CONFIGURAÇÃO INICIAL

1. Clone o repositório:
   git clone <repo_url>
   cd shc-backend
   composer install

2. Configure o arquivo .env:
   Copie .env.example → .env e ajuste banco de dados.

3. Execute as migrations:
php artisan migrate:fresh 

php artisan db:seed 

php artisan storage:link 

→ Será criado usuários como especificado no seeder

Exemplo login admin:
Email: admin@fmp.edu.br
Senha: admin123

---
### 3. AUTENTICAÇÃO E SEGURANÇA

#### Cabeçalhos obrigatórios:
#### Accept: application/json
#### Authorization: Bearer {token}
----------------------------------------------------------------

TABELA — Auth Endpoints
----------------------------------------------------------------
Método   | Endpoint              | Descrição                          | Acesso
---------|------------------------|--------------------------------------|---------
POST     | /auth/login           | Realiza login e retorna token       | Público
POST     | /auth/logout          | Revoga token                        | Autenticado
POST     | /auth/change-password | Altera senha                        | Autenticado
----------------------------------------------------------------

Exemplo payload:
```json
{
  "cpf": "000.000.000-00",
  "password": "senha_secreta"
}
```

---
### 4. CERTIFICADOS — ENDPOINTS

TABELA — Endpoints de Certificados
-------------------------------------------------------------------------------------
Método   | Endpoint                     | Descrição                               | Acesso
---------|-------------------------------|-------------------------------------------|--------------------
GET      | /certificados                 | Lista certificados (com filtros)         | Autenticado
POST     | /certificados                 | Envia novo certificado                   | Aluno
GET      | /certificados/{id}            | Detalhes do certificado                  | Dono/Coord/Admin
PATCH    | /certificados/{id}/avaliar    | Aprovar/Reprovar certificado             | Coordenador
-------------------------------------------------------------------------------------


#### Filtros de busca GET /certificados

status        → ENTREGUE, APROVADO, REPROVADO  
aluno_id      → filtra por aluno  
search        → busca por nome/CPF  
data_inicio   → YYYY-MM-DD  
data_fim      → YYYY-MM-DD  
curso_id      → filtra por curso  


#### Regras por Perfil

Aluno:            filtros aplicam somente ao próprio aluno  
Coordenador:      pode filtrar por aluno_id e status=ENTREGUE  
Secretaria/Admin: acesso geral, filtros amplos  


#### Payload — Envio de Certificado (multipart/form-data)

categoria_id  
nome_certificado  
instituicao  
data_emissao (Y-m-d)  
carga_horaria_solicitada (int)  
arquivo (.pdf, até 10MB)

#### Payload — Avaliação do Coordenador
```json
{
  "status": "APROVADO",
  "horas_validadas": 10,
  "observacao": "Validação ok."
}
```

---
### 5. USUÁRIOS — CRUD / PERFIL

TABELA — Endpoints de Usuários
----------------------------------------------------------------------------------------
Método   | Endpoint                  | Descrição                                | Acesso
---------|----------------------------|--------------------------------------------|--------------------
GET      | /usuarios                 | Lista usuários                             | Admin/Secretaria
POST     | /usuarios                 | Cria novo usuário                          | Admin/Secretaria
PUT      | /usuarios/{id}            | Atualiza dados                             | Admin/Sec/Próprio
DELETE   | /usuarios/{id}            | Remove usuário                             | Admin/Secretaria
GET      | /usuarios/{id}/progresso  | Retorna progresso de horas                 | Regra*
POST     | /usuarios/avatar          | Atualiza foto do próprio usuário           | Próprio Usuário
----------------------------------------------------------------------------------------

Regras de Edição:
- Admin/Secretaria → podem alterar tudo  
- Próprio usuário → apenas nome + email  
- Senha → /auth/change-password  
- Avatar → /usuarios/avatar  

Nota sobre Criação (POST):

O campo password é opcional. Se não enviado, a senha inicial será a data_nascimento formatada apenas com números (ex: 25122000).

Payload exemplo:
```json
{
  "nome": "João Silva Editado",
  "email": "joao.novo@email.com",
  "cpf": "000.111.222-33",
  "data_nascimento": "2000-12-25",  // Obrigatório (Formato YYYY-MM-DD)
  "tipo": "ALUNO",
  "curso_id": 1,
  "fase": 6
}
```


Modelo de retorno:
```json
{
  "id": 1,
  "nome": "João Silva",
  "email": "joao@email.com",
  "cpf": "000.111.222-33",
  "data_nascimento": "2000-12-25",
  "tipo": "ALUNO",
  "curso": {
    "id": 1,
    "nome": "Direito"
  },
  "fase": 5,
  "avatar_url": "http://localhost:8000/storage/avatars/exemplo.jpg"
}
```

---
### 6. EXEMPLOS DE USO

1. Coordenador vendo certificados de um aluno:
   GET /api/certificados?aluno_id=42
   Authorization: Bearer {token}

2. Secretaria buscando aluno por nome:
   GET /api/certificados?search=Maria&curso_id=3

3. Aluno editando seus dados:
   PUT /api/usuarios/10
   Authorization: Bearer {token}
```json
   {
  "nome": "Maria Souza Alterado",
  "email": "maria@email.com",
  "data_nascimento": "1998-05-20",
  "tipo": "ALUNO",
  "curso_id": 3,
  "fase": 4
   }
```

---
### 7. CONFIGURAÇÕES E CURSOS


TABELA — Endpoints de Configurações e Cursos
------------------------------------------------------------
Método   | Endpoint         | Descrição                      | Acesso
---------|-------------------|--------------------------------|-----------
GET      | /configuracoes    | Retorna regras do sistema      | Admin
PUT      | /configuracoes    | Atualiza regras                | Admin
GET      | /cursos           | Lista cursos                   | Autenticado
POST     | /cursos           | Cria um novo curso             | Admin
GET      | /cursos/{id}      | Vê detalhes de um curso        | Autenticado
PUT      | /cursos/{id}      | Atualiza nome ou horas         | Admin
DELETE   | /cursos/{id}      | Remove curso (se sem alunos)   | Admin
------------------------------------------------------------


Payload Exemplo (JSON):
```json
{
    "nome": "Engenharia de Software",
    "horas_necessarias": 250
}
```

### 8. DICIONÁRIO DE DADOS (ENUMS)

Tipo de Usuário:
- ALUNO
- COORDENADOR
- SECRETARIA
- ADMINISTRADOR

Status do Certificado:
- ENTREGUE
- APROVADO
- REPROVADO
- APROVADO_COM_RESSALVAS
---
### 9. ERROS

Erros comuns:
- 401 → Token inválido/ausente
- 403 → Sem permissão
- 422 → Erro de validação

Exemplo 422:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cpf": ["O campo cpf é obrigatório."]
  }
}
```

---
### 10. CATEGORIA — ENDPOINTS

TABELA — Endpoints de Categorias
Método   | Endpoint        | Descrição                          | Permissão
---------|------------------|--------------------------------------|--------------------
GET      | /categorias      | Lista todas as categorias disponíveis. | Autenticado (Todos)
POST     | /categorias      | Cria uma nova categoria.              | Admin
DELETE   | /categorias/{id} | Remove uma categoria.                 | Admin
------------------------------------------------------------

### 11. AUDIT — TABELA DE ALTERACOES (User e Certificado)
Usa observers para registrar alteracoes na tabela audit.
#### Exemplo — Coordenador aprova um certificado.
```json
{
  "id": 105,
  "user_id": 2, // ID do Coordenador
  "event": "updated",
  "auditable_type": "App\\Models\\Certificado",
  "auditable_id": 45,
  "old_values": {
    "status": "ENTREGUE",
    "horas_validadas": null
  },
  "new_values": {
    "status": "APROVADO",
    "horas_validadas": 20
  },
  "ip_address": "192.168.1.15",
  "created_at": "2025-11-25 10:30:00"
}
```

### 11. Importação de Usuários

Endpoint administrativo para criar ou atualizar usuários em lote via JSON.

**Endpoint**
- **POST** `/usuarios/import` — Importa ou atualiza usuários. (Permissão: Admin)

**Regras Principais**
1. **Identificação por CPF**  
   - CPF existente → atualiza.  
   - CPF novo → cria usuário.

2. **Senhas**  
   - `password` vazio → usa `data_nascimento` (formato `dmY`).  
   - Texto simples → sistema aplica hash.  
   - Hash começando com `$2y$` → mantido (útil para migração).

**Payload**
- Objeto contendo `usuarios` (lista de objetos).
- Headers: `Content-Type: application/json` e `Authorization: Bearer {token_admin}`.

**Exemplo**
```json
{
  "usuarios": [
    {
      "nome": "Novo Aluno Exemplo",
      "email": "aluno.novo@fmp.edu.br",
      "cpf": "123.456.789-00",
      "data_nascimento": "2000-05-20",
      "tipo": "ALUNO",
      "curso_id": 1,
      "fase": 1,
      "matricula": "2024001"
    },
    {
      "nome": "Professor Coordenador",
      "email": "coord@fmp.edu.br",
      "cpf": "999.888.777-66",
      "tipo": "COORDENADOR",
      "curso_id": 2,
      "password": "senha_segura_personalizada"
    }
  ]
}
```

**Resposta**
```json
{
  "message": "Importação concluída. 2 usuários processados."
}
```
