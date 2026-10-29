# tests — ejemplos de llamadas CRUD por GraphQL

Llamadas manuales contra el stack local (`make up`). Copiadas de task-php (mismo contrato) y adaptadas al puerto de task-rust (9500).
Los tests automáticos están en `tests/modules/graphql/infrastructure/controllers/graphql_controller` (`make test f="--test graphql_controller_test"`).

## 1 - Antes de empezar

1. Endpoint: `POST http://localhost:9500/graphql` con body `{"query": "...", "variables": {...}}`.
2. Cabecera `Authorization: Bearer <api_token>` en todo salvo `createUser` y `logIn`.
3. Para no darse de alta se puede usar el **usuario demo** (migraciones `20261024165520-seed-demo-user-tasker.sql` y `20261025101838-add-password-hash-to-users.sql`):

| # | Campo | Valor |
|---|---|---|
| 1 | `user_uuid` | `6e142a62-5af7-4d43-b786-cec6ac17be62` |
| 2 | `user_name` | `tasker` |
| 3 | `user_email` | `tasker@tasker.loc` |
| 4 | `password` | `Demo.1234` |

   Su `api_token` cambia con cada login: este paso lo guarda en `$TOKEN`, que usan las llamadas de abajo.

```bash
TOKEN=$(curl -s -X POST 'http://localhost:9500/graphql' --header 'Content-Type: application/json' \
--data '{"query": "mutation { logIn(user_name: \"tasker\", password: \"Demo.1234\") { api_token } }"}' \
| sed -E 's/.*"api_token":"([^"]+)".*/\1/'); echo "$TOKEN"
```

4. Los valores que cambian de una llamada a otra (uuids, textos) van en `variables`: así no hay que escapar comillas dentro de la query.
5. Un error de dominio responde `200` con `errors[].extensions.code` = el código HTTP equivalente (`400`, `401`, `404`, `409`).

## 2 - Tareas (CRUD)

### 2.1 Crear

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($title: String!) { createTask(task_title: $title, task_type: \"work\", target_date: \"2026-12-31\") { task_uuid task_title task_status task_type target_date } }",
  "variables": {"title": "Preparar la demo"}
}'
```

```json
{"data":{"createTask":{"task_uuid":"task-671b9c4e2a1f3","task_title":"Preparar la demo","task_status":"pending","task_type":"work","target_date":"2026-12-31"}}}
```

### 2.2 Listar mis tareas

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{"query": "{ tasks(page: 1, page_size: 10) { items { task_id task_uuid task_title task_status } page total_items total_pages } }"}'
```

```json
{"data":{"tasks":{"items":[{"task_id":42,"task_uuid":"97b0775b-502c-4b29-9585-c024ab39fb98","task_title":"Preparar la demo","task_status":"pending"}],"page":1,"total_items":1,"total_pages":1}}}
```

Siempre de la más reciente a la más antigua (`task_id` desc). Con filtros (todos opcionales; `page_size` de 1 a 100):

```bash
curl -s -X POST 'http://localhost:9500/graphql' --header "Authorization: Bearer $TOKEN" --header 'Content-Type: application/json' --data '{
  "query": "query($filter: TasksFilter, $page: Int, $pageSize: Int) { tasks(filter: $filter, page: $page, page_size: $pageSize) { items { task_id task_title target_date } total_items total_pages } }",
  "variables": {"filter": {"task_text": "demo", "task_status": "pending", "target_date_from": "2026-12-01", "target_date_to": "2026-12-31"}, "page": 1, "pageSize": 5}
}'
```

```json
{"data":{"tasks":{"items":[{"task_id":42,"task_title":"Preparar la demo","target_date":"2026-12-31"}],"total_items":1,"total_pages":1}}}
```

### 2.3 Leer una tarea

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{
  "query": "query($taskUuid: ID!) { task(task_uuid: $taskUuid) { task_uuid task_title task_status target_date } }",
  "variables": {"taskUuid": "<task_uuid>"}
}'
```

```json
{"data":{"task":{"task_uuid":"97b0775b-502c-4b29-9585-c024ab39fb98","task_title":"Preparar la demo","task_status":"pending","target_date":"2026-12-31"}}}
```

### 2.4 Usuario y sus tareas en una sola consulta

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{
  "query": "query($userUuid: ID!) { user(user_uuid: $userUuid) { user_name tasks(page_size: 5) { items { task_title task_status } total_items } } }",
  "variables": {"userUuid": "6e142a62-5af7-4d43-b786-cec6ac17be62"}
}'
```

```json
{"data":{"user":{"user_name":"tasker","tasks":{"items":[{"task_title":"Preparar la demo","task_status":"pending"}],"total_items":1}}}}
```

### 2.5 Modificar

`updateTask` es un reemplazo completo: lo que no se envía vuelve a su valor por defecto o queda vacío.

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($taskUuid: ID!, $title: String!, $status: String) { updateTask(task_uuid: $taskUuid, task_title: $title, task_status: $status, task_type: \"work\") { task_uuid task_status updated_at } }",
  "variables": {"taskUuid": "<task_uuid>", "title": "Preparar la demo", "status": "done"}
}'
```

```json
{"data":{"updateTask":{"task_uuid":"97b0775b-502c-4b29-9585-c024ab39fb98","task_status":"done","updated_at":"2026-10-24 16:57:29"}}}
```

### 2.6 Borrar

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($taskUuid: ID!) { deleteTask(task_uuid: $taskUuid) { task_uuid affected } }",
  "variables": {"taskUuid": "<task_uuid>"}
}'
```

```json
{"data":{"deleteTask":{"task_uuid":"97b0775b-502c-4b29-9585-c024ab39fb98","affected":1}}}
```

## 3 - Usuarios (CRUD)

### 3.1 Alta (sin token)

`user_name` es el login (3-50: letras, dígitos, punto, guion bajo y guion; único sin distinguir mayúsculas) y `password` va de 8 a 72 caracteres. Devuelve el `api_token`.

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($name: String!, $email: String!, $password: String!) { createUser(user_name: $name, user_email: $email, password: $password) { user_uuid user_name user_email api_token } }",
  "variables": {"name": "luis", "email": "graphql.demo@example.com", "password": "Luis.Secret1"}
}'
```

```json
{"data":{"createUser":{"user_uuid":"4cc77f3c-b31d-428b-a58e-8c411ca95209","user_name":"luis","user_email":"graphql.demo@example.com","api_token":"87b56212b83e7deeefa22fd5ecaf73cc29785884fe57787ba5340cc2c42f7118"}}}
```

### 3.2 Login (sin token)

Devuelve el usuario y un `api_token` nuevo: el anterior deja de valer. Usuario inexistente o password incorrecta responden igual (`401`).

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($name: String!, $password: String!) { logIn(user_name: $name, password: $password) { user_uuid user_name api_token } }",
  "variables": {"name": "luis", "password": "Luis.Secret1"}
}'
```

```json
{"data":{"logIn":{"user_uuid":"4cc77f3c-b31d-428b-a58e-8c411ca95209","user_name":"luis","api_token":"5706141312cc621dd83a58a97ace6e979719b8dab21342beed35beee19dad6f5"}}}
```

### 3.3 Leer (mi usuario)

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "{ users { user_uuid user_name user_email } }"}'
```

```json
{"data":{"users":[{"user_uuid":"6e142a62-5af7-4d43-b786-cec6ac17be62","user_name":"tasker","user_email":"tasker@tasker.loc"}]}}
```

### 3.4 Modificar

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($userUuid: ID!, $name: String!, $email: String!) { updateUser(user_uuid: $userUuid, user_name: $name, user_email: $email) { user_uuid user_name updated_at } }",
  "variables": {"userUuid": "<user_uuid>", "name": "luis.perez", "email": "graphql.demo@example.com"}
}'
```

```json
{"data":{"updateUser":{"user_uuid":"4cc77f3c-b31d-428b-a58e-8c411ca95209","user_name":"luis.perez","updated_at":"2026-10-24 16:57:54"}}}
```

### 3.5 Regenerar el api_token

El token anterior deja de valer en el acto (responde `401`).

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($userUuid: ID!) { regenerateApiToken(user_uuid: $userUuid) { user_uuid api_token } }",
  "variables": {"userUuid": "<user_uuid>"}
}'
```

```json
{"data":{"regenerateApiToken":{"user_uuid":"4cc77f3c-b31d-428b-a58e-8c411ca95209","api_token":"2c91f65e4870ddb69bf62cf33db63a556a64e4fcda12d697af9091325915d9a2"}}}
```

### 3.6 Borrar

Borra también todas sus tareas.

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation($userUuid: ID!) { deleteUser(user_uuid: $userUuid) { user_uuid affected } }",
  "variables": {"userUuid": "<user_uuid>"}
}'
```

```json
{"data":{"deleteUser":{"user_uuid":"4cc77f3c-b31d-428b-a58e-8c411ca95209","affected":1}}}
```

## 4 - Errores

### 4.1 Validación (400)

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header "Authorization: Bearer $TOKEN" \
--header 'Content-Type: application/json' \
--data '{
  "query": "mutation { createTask(task_title: \"x\", task_status: \"archived\") { task_uuid } }"
}'
```

```json
{"errors":[{"message":"Fields validation errors: task_status: allowed values are pending, in_progress, done","path":["createTask"],"extensions":{"code":400,"errors":["Fields validation errors: task_status: allowed values are pending, in_progress, done"]}}],"data":{"createTask":null}}
```

### 4.2 Sin token (401)

```bash
curl -s -X POST 'http://localhost:9500/graphql' \
--header 'Content-Type: application/json' \
--data '{"query": "{ tasks { total_items } }"}'
```

```json
{"errors":[{"message":"Missing or invalid API token","path":["tasks"],"extensions":{"code":401,"errors":["Missing or invalid API token"]}}]}
```

### 4.3 Recurso que no existe o no es tuyo (404) · user_name o email repetido (409) · login incorrecto (401)

1. `task(task_uuid: "<uuid de otro usuario>")` → `extensions.code: 404` y `data.task: null`.
2. `createUser` con un email ya registrado → `extensions.code: 409` (`Email ... already exists`).
3. `createUser` con un `user_name` ya registrado, aunque cambien las mayúsculas → `extensions.code: 409` (`User name ... already exists`).
4. `logIn` con password incorrecta o `user_name` inexistente → `extensions.code: 401` (`Invalid user name or password`).
