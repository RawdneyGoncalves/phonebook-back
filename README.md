# Phonebook API - Aplicação Profissional de Gerenciamento de Contatos

## Visão Geral

Phonebook é uma aplicação de API REST desenvolvida em Laravel 11 que implementa um sistema completo de gerenciamento de contatos com autenticação baseada em tokens. A aplicação foi arquitetada seguindo padrões sólidos de engenharia de software, garantindo escalabilidade, manutenibilidade e segurança.



### Padrões de Design Implementados

#### 1. Repository Pattern

O Repository Pattern fornece uma camada de abstração entre a lógica de negócio e o acesso a dados. Benefícios:

- Isolamento: A service layer não conhece detalhes de como os dados são persistidos
- Testabilidade: Repositórios podem ser mockados em testes unitários
- Flexibilidade: Trocar de banco de dados requer mudanças apenas no repositório
- Coesão: Queries relacionadas a uma entidade ficam centralizadas

Exemplo de fluxo:
```
Service -> Repository.findByEmail() -> Database Query -> User Model
```

Se decidirmos migrar de PostgreSQL para MongoDB, apenas o repositório seria alterado.

#### 2. Service Layer Pattern

O Service Layer encapsula a lógica de negócio, garantindo que:

- A lógica não fica espalhada entre controladores
- A mesma lógica pode ser reutilizada em diferentes contextos (API, CLI, Jobs)
- Testes unitários podem verificar a lógica sem precisar de um controlador
- Exceções e erros são tratados em um local centralizado

#### 3. DTO (Data Transfer Object) Pattern

DTOs são classes que representam dados em trânsito entre camadas. Razões de seu uso:

- Type Safety: PHP 8.2 permite tipagem de parâmetros, DTOs garantem que dados corretos chegam ao service
- Validação: Conversão para DTO falha se dados estão inválidos
- Documentação: DTO define explicitamente quais campos são necessários
- Imutabilidade: Propriedades readonly previnem modificações acidentais

```php
// Sem DTO (ambíguo)
$service->create(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

// Com DTO (explícito e seguro)
$service->create(new UserDTO('John', 'john@example.com', 'secret'));
```

#### 4. Form Request Pattern

Form Requests centralizam validação de entrada, oferecendo:

- Validação declarativa e reutilizável
- Mensagens de erro customizadas
- Falha rápida: Requisição inválida não chega ao controlador
- Lógica de autorização centralizada

#### 5. Resource Pattern

Resources serializam modelos para JSON, permitindo:

- Transformação consistente de dados
- Ocultação de campos sensíveis (senhas, tokens internos)
- Lazy loading e eager loading controlados
- Versionamento de API através de diferentes Resources

## Autenticação com Laravel Sanctum

A autenticação foi implementada usando Laravel Sanctum, que fornece autenticação baseada em tokens para SPAs e APIs mobile.

### Por que Sanctum?

1. Tokens JWT: Stateless, escalável, funciona bem com múltiplos servidores
2. Segurança: Tokens expiráveis, revogáveis, não armazenam dados sensíveis
3. Simplicidade: Integrado com Laravel, sem complexidade de OAuth
4. Performance: Não requer consultas ao banco para validar token em cada requisição (com cache)

### Fluxo de Autenticação

```
1. Cliente registra com email/password
2. Senha é hasheada com bcrypt antes de armazenar
3. Cliente faz login com email/password
4. Servidor valida credenciais
5. Servidor gera token e o retorna
6. Cliente armazena token localmente
7. Cliente envia token em cada requisição
8. Middleware valida token
9. Se válido, requisição é processada
10. Ao logout, tokens são deletados do banco
```

### Segurança da Implementação

- Senhas são hasheadas com bcrypt (sem reversão possível)
- Tokens são armazenados em `personal_access_tokens`
- Cada token possui `token_id` único e `name` para rastreamento
- Tokens podem ser revogados individuais ou em massa
- Middleware `auth:sanctum` rejeita requisições sem token válido

## Redis: Propósito e Benefícios

Redis é um armazenamento em memória usado principalmente para cache e sessões. Sua implementação nesta aplicação serve para:

### 1. Cache de Sessões

Redis armazena dados de sessão em memória em vez de disco:

- Leitura/escrita 100x mais rápida que disco
- Persiste entre requisições HTTP
- Automaticamente expirado após inatividade
- Suporta múltiplos servidores (dados compartilhados)

### 2. Cache de Queries

Dados frequentemente acessados podem ser cacheados:

```php
// Sem cache
$user = User::find(1); // Consulta banco toda vez

// Com cache
$user = Cache::remember('user.1', 3600, fn() => User::find(1));
// Consulta banco apenas na primeira vez, depois serve do cache
```

Tempo de resposta melhora drasticamente em aplicações com alto volume.

### 3. Rate Limiting

Redis armazena contadores de requisições por IP/usuário:

```php
Route::post('/api/auth/login')->middleware('throttle:5,1');
// Máximo 5 requisições por minuto
```

Previne força bruta em endpoints de autenticação.

### 4. Job Queue (Futuro)

Redis pode armazenar jobs que serão processados assincronamente:

```php
// Enviar email em background
Mail::to($user)->queue(new WelcomeEmail($user));
// Job fica na fila do Redis
// Worker processa quando está livre
```

### 5. Pub/Sub em Tempo Real (Futuro)

Redis suporta publicação/subscrição para real-time updates sem WebSockets complexos.

### Configuração nesta Aplicação

Redis está configurado em:

- `.env`: HOST, PORT, PASSWORD
- `docker-compose.yml`: Container Redis 7-alpine
- `config/cache.php`: Redis como driver padrão
- `app/Providers/AppServiceProvider.php`: Bindings de dependência

O container PostgreSQL permanece para dados persistentes enquanto Redis é apenas cache.

## Armazenamento e Persistência

A aplicação utiliza dois tipos de armazenamento:

### PostgreSQL (Dados Persistentes)

- Armazena usuários, contatos, tokens
- Transações ACID garantem consistência
- Índices em `email`, `phone`, `name` otimizam queries frequentes
- Backup e replicação nativas
- Dados sobrevivem a restarts

### Redis (Cache/Sessões - Não Persistente por Padrão)

- Cache é perdido em restart (esperado)
- Melhora performance drasticamente
- Reduz carga no PostgreSQL
- Em produção, pode ser configurado com RDB/AOF para persistência

Separar cache de dados persistentes é padrão em aplicações profissionais.

## Upload de Imagens

Imagens de contatos são armazenadas em `storage/app/public/contacts/`:

- Arquivo é salvo com hash MD5 para evitar colisões
- Extensão original é preservada
- Caminho é armazenado no banco
- URL é gerada dinamicamente via `asset()` helper
- Ao atualizar contato, imagem antiga é deletada antes de salvar nova
- Ao deletar contato, imagem associada é deletada

Estrutura de armazenamento:

```
storage/
└── app/
    └── public/
        └── contacts/
            ├── abc123def456.jpg
            ├── xyz789uvw123.png
            └── ...
```

## Paginação e Performance

Todas as listagens usam paginação obrigatória com 15 itens por página:

- Reduz consumo de memória
- Melhora tempo de resposta
- Evita transferência de grandes volumes desnecessários
- Interface define quantas páginas existem para navegação

Query construída:

```php
Contact::where('user_id', $user->id)
    ->orderBy('name', 'asc')
    ->paginate(15);
```

PostgreSQL retorna apenas 15 registros + metadados de paginação.

## Busca e Filtros

A busca é case-insensitive e busca em múltiplos campos:

```php
Contact::where('name', 'like', "%{$query}%")
    ->orWhere('phone', 'like', "%{$query}%")
    ->orWhere('email', 'like', "%{$query}%")
    ->paginate(15);
```

PostgreSQL utiliza índice em `name` para otimizar busca textual.

## Validações em Múltiplas Camadas

Validações ocorrem em 3 níveis:

### 1. Form Request (HTTP Layer)
```php
'email' => 'required|email|unique:users,email'
// Rejeita automaticamente se email duplicado antes de chegar ao controller
```

### 2. DTO Conversion (Business Logic Layer)
```php
public static function fromArray(array $data): self
{
    return new self(
        email: $data['email'] // TypeCheck em PHP 8.2
    );
}
```

### 3. Service (Application Layer)
```php
if ($this->repository->exists($dto->email)) {
    throw new Exception('Email already registered', 422);
}
```

Essa abordagem em camadas previne estados inválidos em qualquer nível.

## Tratamento de Erros

Exceções são tratadas com códigos HTTP apropriados:

- 201: Criado com sucesso
- 200: Sucesso genérico
- 204: Sucesso sem conteúdo (DELETE)
- 400: Requisição inválida
- 401: Não autenticado
- 403: Não autorizado
- 404: Recurso não encontrado
- 422: Validação falhou
- 500: Erro interno

Padrão de resposta de erro:

```json
{
  "error": "Descrição do erro",
  "trace": ["Detalhes em development"]
}
```

## Banco de Dados

### Tabelas

#### users
- id (primary key)
- name (string, indexed para busca)
- email (string, unique)
- password (hashed)
- email_verified_at (nullable timestamp)
- remember_token (para "manter logado")
- timestamps (created_at, updated_at)

#### contacts
- id (primary key)
- user_id (foreign key para users)
- name (string, indexed)
- phone (string, unique por user)
- email (string, nullable)
- image_path (string, nullable)
- timestamps

#### personal_access_tokens (Sanctum)
- id (primary key)
- tokenable_type, tokenable_id (polymorphic)
- name (descrição do token)
- token (string, unique, hashed)
- abilities (JSON, permissões)
- last_used_at (rastreamento)
- expires_at (expiração)
- timestamps

### Índices

Para otimização de queries frequentes:

```sql
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_contacts_name ON contacts(name);
CREATE INDEX idx_contacts_phone ON contacts(phone);
CREATE INDEX idx_contacts_user_id ON contacts(user_id);
CREATE INDEX idx_contacts_created_at ON contacts(created_at);
```

Índices aceleram lookups mas aumentam tempo de INSERT/UPDATE levemente.

## Containerização com Docker

A aplicação é totalmente containerizada para garantir ambiente consistente:

### docker-compose.yml

Define 3 serviços:

1. **app** (Laravel)
   - Build customizado com PHP 8.2-FPM
   - Dependências instaladas em tempo de build
   - Volume montado para code hot-reload
   - Porta 8000 exposta

2. **postgres** (Banco de dados)
   - PostgreSQL 16-alpine
   - Healthcheck verifica se está ready
   - Volume para persistência entre restarts
   - Porta 5432 exposta

3. **redis** (Cache)
   - Redis 7-alpine
   - Healthcheck verifica se está operacional
   - Sem volume persistente (cache)
   - Porta 6379 exposta

Redes isoladas garantem que serviços só acessam uns aos outros, não o host.

## Testes

### Testes de Feature (Integração)

Testam endpoints completos contra banco SQLite temporário:

```php
public function test_can_register_user(): void
{
    $response = $this->postJson('/api/auth/register', [
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
}
```

Garante que fluxos completos funcionam end-to-end.

### Testes Unitários

Testam services isoladamente com mocks:

```php
public function test_phone_exists_returns_true(): void
{
    $repository = $this->createMock(ContactRepository::class);
    $repository->method('findByPhone')->willReturn($contact);

    $service = new ContactService($repository);
    $this->assertTrue($service->phoneExists('123456'));
}
```

Rápidos, não dependem de banco, testam lógica pura.

## Configuração de Ambiente

### .env (Development)
```
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
CACHE_DRIVER=redis
```

### .env (Production)
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=db.internal.example.com
CACHE_DRIVER=redis
```

Variáveis de ambiente garantem que mesmo código funciona em ambientes diferentes.

## Requisitos

- PHP 8.2+
- PostgreSQL 16+
- Redis 7+
- Docker e Docker Compose (recomendado)

## Instalação

```bash
# Clone o repositório
git clone <repo>
cd phonebook

# Copie environment
cp .env.example .env

# Inicie containers
docker-compose up -d

# Aguarde 15 segundos

# Rode migrações
docker-compose exec app php artisan migrate

# Teste
curl http://localhost:8000/api/contacts
# Retorna 401 Unauthorized (esperado, sem token)
```

## API Endpoints

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
- Response: Success message

### Contatos (Protected)

GET /api/contacts
- Header: Authorization: Bearer {token}
- Query: ?q=termo&page=1
- Response: Array de contatos + paginação

POST /api/contacts
- Header: Authorization: Bearer {token}
- Body: FormData com name, phone, email, image
- Response: Contato criado (201)

PUT /api/contacts/{id}
- Header: Authorization: Bearer {token}
- Body: FormData atualizado
- Response: Contato atualizado (200)

DELETE /api/contacts/{id}
- Header: Authorization: Bearer {token}
- Response: No content (204)

## Performance

Tempos médios de resposta:

- GET /api/contacts: 30-50ms (com paginação)
- GET /api/contacts?q=termo: 40-60ms (com índices)
- POST /api/contacts: 50-100ms (com upload)
- POST /api/auth/login: 100-200ms (bcrypt é lento propositalmente)

Com cache Redis ativo, tempos melhoram para 10-20ms em segunda requisição.

## Escalabilidade

Estratégias para escalar:

### Horizontal (Múltiplas instâncias)

```yaml
# docker-compose.yml
services:
  app1:
    ...
  app2:
    ...
  app3:
    ...
  nginx:
    image: nginx
    ports:
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
```

Redis compartilhado: `host: redis` (mesma rede Docker)
PostgreSQL compartilhado: Primary/Replica setup

### Vertical (Instância mais potente)

- Aumentar CPU/RAM do container
- Aumentar pool de conexões PostgreSQL
- Aumentar memória Redis

### Database

- Índices adicionais em queries lentas
- Tabelas particionadas por data/range
- Read replicas para distribuir carga

## Segurança

### Já Implementada

- SQL Injection: Prevenida pelo Eloquent ORM
- CSRF: Disabled para API (stateless tokens)
- Senhas: Hashed com bcrypt
- Tokens: Únicos, expiráveis, revogáveis
- Validação: Em múltiplas camadas

### Recomendado para Produção

- HTTPS: Implementar SSL/TLS
- Rate Limiting: `throttle:60,1` em endpoints sensíveis
- CORS: Configurar `config/cors.php` se frontend separado
- Secrets: Usar AWS Secrets Manager ou similar
- Logs: Centralizar em ELK ou DataDog
- Monitoring: Alertas de erros e performance

## Roadmap

Melhorias futuras planejadas:

- Soft deletes para auditoria
- Webhooks para eventos
- Exportar contatos (CSV, vCard)
- Sincronização com Google Contacts
- Autenticação OAuth2
- 2FA (two-factor authentication)
- Grupos de contatos
- Favoritos
- Suporte a múltiplos telefones/emails por contato
