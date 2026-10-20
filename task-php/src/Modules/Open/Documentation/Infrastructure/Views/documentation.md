# Task PHP CRUD API - [{{appVersion}}]({{appBaseUrl}}/api/v1/documentation/changelog#{{appVersion}})
- Última actualización: {{appVersionUpdate}}
- GraphQL (`/graphql`) y API REST (`/api/v1/*`) sobre los mismos casos de uso: lista de tareas por usuario.

---

## 1 - GraphQL

1. Un único endpoint: **POST** `/graphql` con body `{"query": "...", "variables": {...}}` y la cabecera `Authorization: Bearer <api_token>` (ver 2 - Autenticación).
2. Sin token solo funcionan las mutaciones `createUser` (el alta) y `logIn` (el login).
3. Campos y argumentos con las **mismas claves** que el JSON REST (`user_uuid`, `task_title`…).
4. Los errores siguen el estándar GraphQL: la respuesta es `200` y el error va en `errors[]`, con `extensions.code` = el código que daría REST (`400`, `401`, `404`, `409`) y `extensions.errors` = el mensaje (en una validación, con el detalle de cada campo).
5. Profundidad máxima de una consulta: 5 niveles.

### 1.1 Operaciones

| # | Tipo | Operación | Equivale a |
|---|---|---|---|
| 1 | query | `users` | `GET /api/v1/users` |
| 2 | query | `user(user_uuid)` | `GET /api/v1/users/<user_uuid>` |
| 3 | query | `tasks(filter, page, page_size)` | `GET /api/v1/tasks` (aquí paginado y filtrable, ver 1.2) |
| 4 | query | `task(task_uuid)` | `GET /api/v1/tasks/<task_uuid>` |
| 5 | query | `userTasks(user_uuid, filter, page, page_size)` | `GET /api/v1/users/<user_uuid>/tasks` (aquí paginado y filtrable) |
| 6 | mutation | `createUser(user_name, user_email, password)` | `POST /api/v1/users` (sin token) |
| 7 | mutation | `logIn(user_name, password)` | `POST /api/v1/log-in` (sin token) |
| 8 | mutation | `updateUser(user_uuid, user_name, user_email)` | `PUT /api/v1/users/<user_uuid>` |
| 9 | mutation | `deleteUser(user_uuid)` | `DELETE /api/v1/users/<user_uuid>` |
| 10 | mutation | `regenerateApiToken(user_uuid)` | `POST /api/v1/users/<user_uuid>/api-token` |
| 11 | mutation | `createTask(task_title, ...)` | `POST /api/v1/tasks` |
| 12 | mutation | `updateTask(task_uuid, task_title, ...)` | `PUT /api/v1/tasks/<task_uuid>` |
| 13 | mutation | `deleteTask(task_uuid)` | `DELETE /api/v1/tasks/<task_uuid>` |

Además, el tipo `User` tiene el campo `tasks(filter, page, page_size)`: usuario y tareas en una sola consulta.

### 1.2 Listados de tareas: paginación y filtros

`tasks`, `userTasks` y `User.tasks` devuelven una página (`TaskPage`), siempre de la más reciente a la más antigua (`task_id` descendente).

| # | Argumento | Tipo | Qué hace |
|---|---|---|---|
| 1 | `filter.task_text` | String | Contenido en el título o la descripción, sin distinguir mayúsculas |
| 2 | `filter.task_status` | String | `pending` · `in_progress` · `done` |
| 3 | `filter.task_type` | String | `personal` · `work` · `other` |
| 4 | `filter.target_date_from` | String | `YYYY-MM-DD`, incluido (una tarea sin fecha objetivo no entra) |
| 5 | `filter.target_date_to` | String | `YYYY-MM-DD`, incluido; no puede ser anterior a `target_date_from` |
| 6 | `page` | Int | Desde 1 (por defecto 1) |
| 7 | `page_size` | Int | De 1 a 100 (por defecto 20) |

La respuesta: `items` (las tareas de la página), `page`, `page_size`, `total_items` (todas las que cumplen los filtros) y `total_pages`. Un argumento fuera de rango responde con `extensions.code: 400` y un mensaje con el detalle de cada campo.

```
curl -X POST '{{appBaseUrl}}/graphql' --header 'Authorization: Bearer <api_token>' --header 'Content-Type: application/json' --data '{"query": "query($filter: TasksFilter, $page: Int, $pageSize: Int) { tasks(filter: $filter, page: $page, page_size: $pageSize) { items { task_id task_title target_date } page page_size total_items total_pages } }", "variables": {"filter": {"task_text": "demo", "target_date_from": "2026-12-01"}, "page": 1, "pageSize": 10}}'
```

#### respuesta:
```
{
    "data": {
        "tasks": {
            "items": [
                {"task_id": 42, "task_title": "Preparar la demo", "target_date": "2026-12-31"}
            ],
            "page": 1,
            "page_size": 10,
            "total_items": 1,
            "total_pages": 1
        }
    }
}
```

### 1.3 Consulta

```
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "{ users { user_uuid user_name tasks(page_size: 5) { items { task_title task_status } total_items } } }"}'
```

#### respuesta:
```
{
    "data": {
        "users": [
            {
                "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
                "user_name": "ana.lopez",
                "tasks": {
                    "items": [
                        {"task_title": "Preparar la demo", "task_status": "pending"}
                    ],
                    "total_items": 1
                }
            }
        ]
    }
}
```

### 1.4 Mutación con variables

```
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "mutation($id: ID!) { updateTask(task_uuid: $id, task_title: \"Preparar la demo\", task_status: \"done\") { task_uuid task_status } }", "variables": {"id": "<task_uuid>"}}'
```

### 1.5 Error

```
{
    "errors": [
        {
            "message": "Task 7d2e9b40-1f3a-4c8e-b6d5-0a9c8e7f6b21 not found",
            "path": ["task"],
            "extensions": {"code": 404, "errors": ["Task 7d2e9b40-1f3a-4c8e-b6d5-0a9c8e7f6b21 not found"]}
        }
    ],
    "data": {"task": null}
}
```

---

## 2 - Autenticación

1. Cada usuario tiene su propio **api_token**. Se envía en todas las peticiones con la cabecera `Authorization: Bearer <api_token>`.
2. El token se obtiene al **darse de alta** (`POST /api/v1/users`) o al **hacer login** con `user_name` y `password` (`POST /api/v1/log-in`), los dos sin token. Se devuelve **una única vez**: el servidor solo guarda su hash y no puede recuperarlo.
3. **Cada login genera un token nuevo y el anterior deja de valer**: un usuario tiene una sola sesión activa (entrar desde otro sitio cierra la anterior).
4. Si el token se filtra, se **regenera** con `POST /api/v1/users/<user_uuid>/api-token` (o con un login): el anterior deja de valer en el acto.
5. Cada token solo ve y modifica **su propio usuario y sus tareas**. Un recurso de otro usuario responde `404`, igual que si no existiera.
6. Sin token, o con un token desconocido: `401` con la cabecera `WWW-Authenticate: Bearer`.
7. Usa siempre HTTPS fuera de local: el token y la password viajan en la petición.

### 2.1 Flujo rápido

```
# 1. alta: devuelve el usuario y su api_token
curl -X POST '{{appBaseUrl}}/api/v1/users' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "user_email": "ana@example.com", "password": "<password>"}'

# 1b. o, si ya tienes cuenta, login: devuelve el usuario y un api_token nuevo
curl -X POST '{{appBaseUrl}}/api/v1/log-in' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "password": "<password>"}'

# 2. con el token, crear una tarea
curl -X POST '{{appBaseUrl}}/api/v1/tasks' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"task_title": "Comprar pan"}'
```

### 2.2 Usuario demo

Para probar sin darse de alta: haz login con él y usa el `api_token` que devuelve.

| # | Campo | Valor |
|---|---|---|
| 1 | `user_uuid` | `6e142a62-5af7-4d43-b786-cec6ac17be62` |
| 2 | `user_name` | `tasker` |
| 3 | `user_email` | `tasker@tasker.loc` |
| 4 | `password` | `Demo.1234` |

```
curl -X POST '{{appBaseUrl}}/api/v1/log-in' \
--header 'Content-Type: application/json' \
--data '{"user_name": "tasker", "password": "Demo.1234"}'
```

Es un usuario compartido: cada login le da un token nuevo, así que el de otra persona deja de valer.

### 2.3 Cabeceras opcionales

Acompañan a cada petición (REST y GraphQL) y el servidor las registra como contexto de la operación.

| # | Cabecera | Ejemplo | Si no viene o no es válida |
|---|---|---|---|
| 1 | `User-Agent` | `Mozilla/5.0 ... Chrome/141.0.0.0` | Navegador y sistema operativo vacíos |
| 2 | `Accept-Language` | `es-ES,es;q=0.9` | `en` (idiomas: `es`, `en`) |
| 3 | `X-Time-Zone` | `Europe/Madrid` (zona IANA) | Vacía |

---

## 3 - Formato de respuesta (REST)

Todas las respuestas REST usan el mismo envelope JSON:

```
{
    "code": 200,
    "status": true,
    "errors": [],
    "data": {}
}
```

| # | Código | Cuándo |
|---|---|---|
| 1 | `200` | Lectura, modificación o borrado correctos |
| 2 | `201` | Alta correcta (usuario o tarea) |
| 3 | `400` | Validación: `errors` lleva un único mensaje con el detalle de cada campo (`"Fields validation errors: user_name: field is required; user_email: invalid email"`) |
| 4 | `401` | Falta el token o no es válido, o `user_name`/`password` incorrectos en el login (mismo mensaje en los dos casos) |
| 5 | `404` | El recurso no existe o no es tuyo |
| 6 | `405` | Método no permitido en esa url (cabecera `Allow`) |
| 7 | `409` | Conflicto: el `user_name` o el email ya están en uso |
| 8 | `500` | Error inesperado (se registra en el servidor; el mensaje no da detalles) |

---

## 4 - Usuarios

### 4.1 Alta de usuario (sin token)
- **POST** `/api/v1/users`
- Body: `user_name` (obligatorio) · `user_email` (obligatorio, máx. 150, único) · `password` (obligatoria).
- `user_name` es el login: de 3 a 50 caracteres, solo letras, dígitos, punto, guion bajo y guion; único sin distinguir mayúsculas (`Ana` y `ana` son el mismo).
- `password`: de 8 a 72 caracteres. Se guarda solo su hash (bcrypt).
- Respuesta `201`: el usuario y su `api_token`.

```
curl -X POST '{{appBaseUrl}}/api/v1/users' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "user_email": "ana@example.com", "password": "<password>"}'
```

#### respuesta:
```
{
    "code": 201,
    "status": true,
    "errors": [],
    "data": {
        "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
        "user_name": "ana.lopez",
        "user_email": "ana@example.com",
        "created_at": "2026-10-24 10:00:00",
        "updated_at": "2026-10-24 10:00:00",
        "api_token": "9b1f...64 caracteres hex...c0de"
    }
}
```

### 4.2 Login (sin token)
- **POST** `/api/v1/log-in`
- Body: `user_name` (sin distinguir mayúsculas) · `password`.
- Respuesta `200`: el usuario y un `api_token` **nuevo**; el anterior deja de valer.
- `user_name` inexistente, cuenta sin password o password incorrecta: `401` con el mismo mensaje (`Invalid user name or password`), para no revelar qué usuarios existen.

```
curl -X POST '{{appBaseUrl}}/api/v1/log-in' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "password": "<password>"}'
```

### 4.3 Mi usuario
- **GET** `/api/v1/users` · lista con tu usuario (como mucho un elemento).
- **GET** `/api/v1/users/<user_uuid>` · tu usuario por su uuid.

```
curl '{{appBaseUrl}}/api/v1/users/<user_uuid>' \
--header 'Authorization: Bearer <api_token>'
```

### 4.4 Modificar usuario
- **PUT** `/api/v1/users/<user_uuid>` · reemplazo completo: `user_name` y `user_email` obligatorios (mismas reglas que el alta). La password no se cambia aquí.

```
curl -X PUT '{{appBaseUrl}}/api/v1/users/<user_uuid>' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "user_email": "ana@example.com"}'
```

### 4.5 Borrar usuario
- **DELETE** `/api/v1/users/<user_uuid>` · borra también todas sus tareas.
- Respuesta: `{"user_uuid": "...", "affected": 1}` en `data`.

### 4.6 Regenerar el api_token
- **POST** `/api/v1/users/<user_uuid>/api-token` · con el token actual; devuelve el nuevo.

```
curl -X POST '{{appBaseUrl}}/api/v1/users/<user_uuid>/api-token' \
--header 'Authorization: Bearer <api_token>'
```

#### respuesta:
```
{
    "code": 200,
    "status": true,
    "errors": [],
    "data": {
        "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
        "api_token": "nuevo token de 64 caracteres hex"
    }
}
```

---

## 5 - Tareas

### 5.1 Campos de una tarea

| # | Campo | Tipo | Reglas |
|---|---|---|---|
| 1 | `task_title` | string | Obligatorio, máx. 150 |
| 2 | `task_status` | string | `pending` · `in_progress` · `done` (por defecto `pending`) |
| 3 | `task_type` | string | `personal` · `work` · `other` (por defecto `personal`) |
| 4 | `target_date` | string | Opcional, fecha real `YYYY-MM-DD` |
| 5 | `task_description` | string | Opcional, máx. 2000 |

### 5.2 Crear tarea
- **POST** `/api/v1/tasks` · la tarea es del usuario del `api_token`. El `task_uuid` lo genera la API: `task-` + id único.

```
curl -X POST '{{appBaseUrl}}/api/v1/tasks' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"task_title": "Preparar la demo", "task_type": "work", "target_date": "2026-12-31"}'
```

#### respuesta:
```
{
    "code": 201,
    "status": true,
    "errors": [],
    "data": {
        "task_id": 42,
        "task_uuid": "task-671b9c4e2a1f3",
        "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
        "task_title": "Preparar la demo",
        "task_status": "pending",
        "task_type": "work",
        "target_date": "2026-12-31",
        "task_description": null,
        "created_at": "2026-10-24 10:05:00",
        "updated_at": "2026-10-24 10:05:00"
    }
}
```

### 5.3 Listar tareas
- **GET** `/api/v1/tasks` · todas tus tareas, de la más reciente a la más antigua (`task_id` descendente).
- **GET** `/api/v1/users/<user_uuid>/tasks` · las mismas, por tu usuario.
- Para paginar y filtrar, usa GraphQL (ver 1.2).
- `task_id` es solo informativo (orden, referencia legible): para leer, modificar o borrar se usa `task_uuid`.

```
curl '{{appBaseUrl}}/api/v1/tasks' \
--header 'Authorization: Bearer <api_token>'
```

### 5.4 Una tarea
- **GET** `/api/v1/tasks/<task_uuid>`

### 5.5 Modificar tarea
- **PUT** `/api/v1/tasks/<task_uuid>` · reemplazo completo con los campos de 5.1 (lo que no se envía vuelve a su valor por defecto o queda vacío).

```
curl -X PUT '{{appBaseUrl}}/api/v1/tasks/<task_uuid>' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"task_title": "Preparar la demo", "task_status": "done", "task_type": "work"}'
```

### 5.6 Borrar tarea
- **DELETE** `/api/v1/tasks/<task_uuid>` · respuesta: `{"task_uuid": "...", "affected": 1}` en `data`.
