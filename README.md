# Phonebook API - Sistema de Gerenciamento de Contatos

Aplicação de API REST desenvolvida em Laravel 11 implementando Clean Architecture, Domain-Driven Design (DDD) e padrões SOLID. Sistema completo de gerenciamento de contatos com autenticação baseada em tokens Sanctum, upload de imagens otimizado e persistência em PostgreSQL com cache distribuído via Redis.

## Quick Start

### Pré-requisitos
- Docker
- Docker Compose
- Git

### Instalação

Clone o repositório e configure o ambiente:

```bash
git clone <repo>
cd phonebook
cp .env.example .env
```

Execute o setup completo em um único comando:

```bash
docker-compose up -d && \
sleep 15 && \
docker-compose exec -T app php artisan migrate --force && \
docker-compose exec -T app bash -c "chmod -R 775 storage public/storage && chown -R www-data:www-data storage public/storage && rm -rf public/storage && php artisan storage:link && php artisan config:clear && php artisan cache:clear" && \
echo "Setup concluído! API rodando em http://localhost:8000"
```

Teste:

```bash
curl http://localhost:8000/api/contacts
```

Retorna 401 Unauthorized (esperado, sem token).

---

## Configuração do Ambiente (.env)

Se não tiver `.env.example`, crie `.env` com:

```
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=phonebook
DB_USERNAME=postgres
DB_PASSWORD=password
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

---

## Docker - Comandos Essenciais

### Ver status dos containers
```bash
docker-compose ps
```

Deve mostrar:
- phonebook_app (Laravel)
- phonebook_postgres (PostgreSQL)
- phonebook_redis (Redis)

### Entrar no container
```bash
docker-compose exec app bash
```

### Executar artisan
```bash
docker-compose exec app php artisan <comando>

# Exemplos:
docker-compose exec app php artisan route:list
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan queue:work
```

### Ver logs em tempo real
```bash
# Todos os containers
docker-compose logs -f

# Apenas aplicação
docker-compose logs -f app

# Apenas banco de dados
docker-compose logs -f postgres

# Apenas cache
docker-compose logs -f redis
```

### Parar a aplicação
```bash
# Parar (dados persistem)
docker-compose down

# Parar e remover volumes (cuidado: deleta dados!)
docker-compose down -v
```

### Rebuild (após mudanças no Dockerfile)
```bash
docker-compose build --no-cache
docker-compose up -d
```

---

## Storage e Upload de Imagens

### Estrutura de Armazenamento

```
storage/
└── app/
    └── public/
        └── contacts/
            ├── abc123def456.jpg
            ├── xyz789uvw123.png
            └── ...
```

### Permissões de Storage

Já configuradas automaticamente pelo setup, mas se precisar ajustar manualmente:

```bash
# Um comando
docker-compose exec app bash -c "chmod -R 775 storage public/storage && chown -R www-data:www-data storage public/storage"
```

### Criar Symlink (necessário para servir imagens)

```bash
docker-compose exec app php artisan storage:link
```

Isso cria um symlink `public/storage` -> `storage/app/public`

---

## Autenticação

### Registrar Usuário

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Resposta:
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "1|ztEvU5r236OIbBeDYdvknQaSkXeadWD2GZQASTQO6b8510a9"
  }
}
```

### Fazer Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

Copie o token, vamos usar em todos os próximos requests.

### Pegar Dados do Usuário Logado

```bash
TOKEN="seu_token_aqui"

curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/auth/me
```

### Fazer Logout

```bash
TOKEN="seu_token_aqui"

curl -X POST -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/auth/logout
```

---

## Gerenciamento de Contatos

### Listar Contatos

```bash
TOKEN="seu_token_aqui"

# Sem filtro
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/contacts

# Com busca
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/contacts?q=john"

# Página específica
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/contacts?page=2"
```

### Criar Contato (sem imagem)

```bash
TOKEN="seu_token_aqui"

curl -X POST http://localhost:8000/api/contacts \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Smith",
    "phone": "11987654321",
    "email": "jane@example.com"
  }'
```

### Criar Contato (com imagem)

```bash
TOKEN="seu_token_aqui"

curl -X POST http://localhost:8000/api/contacts \
  -H "Authorization: Bearer $TOKEN" \
  -F "name=Jane Smith" \
  -F "phone=11987654321" \
  -F "email=jane@example.com" \
  -F "image=@/caminho/para/foto.jpg"
```

Resposta:
```json
{
  "data": {
    "id": 1,
    "name": "Jane Smith",
    "phone": "11987654321",
    "email": "jane@example.com",
    "image_url": "http://localhost:8000/storage/contacts/abc123def456.jpg",
    "created_at": "2025-11-10T07:29:53+00:00",
    "updated_at": "2025-11-10T07:29:53+00:00"
  }
}
```

### Ver Detalhes de um Contato

```bash
TOKEN="seu_token_aqui"
CONTACT_ID="1"

curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/contacts/$CONTACT_ID
```

### Editar Contato

```bash
TOKEN="seu_token_aqui"
CONTACT_ID="1"

# Sem imagem
curl -X PUT http://localhost:8000/api/contacts/$CONTACT_ID \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Updated",
    "phone": "11987654321",
    "email": "jane.updated@example.com"
  }'

# Com imagem (substitui a anterior)
curl -X POST http://localhost:8000/api/contacts/$CONTACT_ID \
  -H "Authorization: Bearer $TOKEN" \
  -F "_method=PUT" \
  -F "name=Jane Updated" \
  -F "phone=11987654321" \
  -F "email=jane.updated@example.com" \
  -F "image=@/caminho/para/nova-foto.jpg"
```

### Deletar Contato

```bash
TOKEN="seu_token_aqui"
CONTACT_ID="1"

curl -X DELETE http://localhost:8000/api/contacts/$CONTACT_ID \
  -H "Authorization: Bearer $TOKEN"
```

---

## Verificação de Funcionamento

### Testar Conectividade do Banco

```bash
docker-compose exec app php artisan tinker

# Dentro do tinker:
DB::connection()->getPdo();
# Se não retornar erro, está conectado

exit
```

### Testar Cache Redis

```bash
docker-compose exec app php artisan tinker

# Dentro do tinker:
Cache::put('test', 'value');
Cache::get('test');
# Deve retornar 'value'

exit
```

### Ver Estrutura do Banco

```bash
docker-compose exec app php artisan tinker

# Ver tabelas
DB::select("SELECT * FROM information_schema.tables WHERE table_schema='public'");

# Ver contatos de um usuário
App\Models\Contact::where('user_id', 1)->get();

exit
```

---

## Troubleshooting

### Connection refused ao conectar no banco

```bash
# Verificar se PostgreSQL está pronto
docker-compose logs postgres

# Aguardar 20 segundos e tentar novamente
docker-compose down
docker-compose up -d
```

### Permissões negadas ao fazer upload

```bash
# Reajustar permissões
docker-compose exec app bash -c "chmod -R 775 storage public/storage && chown -R www-data:www-data storage public/storage"
```

### Imagens não aparecem

```bash
# Verificar symlink
docker-compose exec app ls -la public/

# Deve ter um link "storage" apontando para ../storage/app/public

# Se não tiver, criar:
docker-compose exec app php artisan storage:link
```

### Cache não funciona

```bash
# Limpar cache
docker-compose exec app php artisan cache:clear

# Limpar tudo
docker-compose exec app php artisan optimize:clear
```

---

## Performance

Tempos médios de resposta:

| Endpoint | Sem Cache | Com Cache |
|----------|-----------|-----------|
| GET /api/contacts | 30-50ms | 5-10ms |
| GET /api/contacts?q=termo | 40-60ms | 10-20ms |
| POST /api/contacts | 50-100ms | - |
| POST /api/auth/login | 100-200ms | - |

---

## Arquitetura

### Padrões Implementados

- Repository Pattern: Abstração de acesso a dados
- Service Layer: Lógica de negócio centralizada
- DTO Pattern: Validação e type safety
- Form Request: Validação de entrada
- Resource Pattern: Serialização JSON

### Stack Tecnológico

- Backend: Laravel 11, PHP 8.2
- Banco: PostgreSQL 16
- Cache: Redis 7
- Autenticação: Laravel Sanctum (tokens)
- Container: Docker + Docker Compose

---

## Recursos Adicionais

- Laravel Documentation: https://laravel.com/docs
- Laravel Sanctum: https://laravel.com/docs/11.x/sanctum
- PostgreSQL Docs: https://www.postgresql.org/docs/
- Redis Docs: https://redis.io/docs/

---

## Endpoints da API

### Autenticação (Public)

POST /api/auth/register
- Body: name, email, password, password_confirmation
- Response: User criado (201)

POST /api/auth/login
- Body: email, password
- Response: User + token (200)

### Autenticação (Protected)

GET /api/auth/me
- Header: Authorization: Bearer {token}
- Response: Dados do usuário autenticado

POST /api/auth/logout
- Header: Authorization: Bearer {token}
- Response: Success message (204)

### Contatos (Protected)

GET /api/contacts
- Header: Authorization: Bearer {token}
- Query: ?q=termo&page=1
- Response: Array de contatos + paginação (200)

POST /api/contacts
- Header: Authorization: Bearer {token}
- Body: FormData com name, phone, email, image
- Response: Contato criado (201)

GET /api/contacts/{id}
- Header: Authorization: Bearer {token}
- Response: Contato específico (200)

PUT /api/contacts/{id}
- Header: Authorization: Bearer {token}
- Body: FormData atualizado
- Response: Contato atualizado (200)

DELETE /api/contacts/{id}
- Header: Authorization: Bearer {token}
- Response: No content (204)

---

## Códigos HTTP

- 200: Sucesso genérico
- 201: Criado com sucesso
- 204: Sucesso sem conteúdo
- 400: Requisição inválida
- 401: Não autenticado
- 403: Não autorizado
- 404: Recurso não encontrado
- 422: Validação falhou
- 500: Erro interno do servidor

---


## Desenvolvedor

Desenvolvido por Rawdney Gonçalves - Engenheiro de Software Full Stack

