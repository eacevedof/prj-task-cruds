# Task PHP CRUD API - changelog

⬅️ [volver a la documentación]({{appBaseUrl}}/api/v1/documentation)

## 1.2.0
- Fecha: 2026-10-25

**Cambios que rompen compatibilidad:**
- Crear tarea pasa a `POST /api/v1/tasks` (antes `POST /api/v1/users/<user_uuid>/tasks`): la tarea es siempre del usuario del `api_token`. La mutación `createTask` pierde el argumento `user_uuid`.
- `task_uuid` pasa a tener el formato `task-` + id único (p. ej. `task-671b9c4e2a1f3`). Las tareas existentes conservan su uuid con el prefijo delante.

## 1.1.0
- Fecha: 2026-10-25

**Nuevo:**
- **Login con `user_name` y `password`**: `POST /api/v1/log-in` y la mutación `logIn`, sin token. Devuelven el usuario y un `api_token` nuevo; el anterior deja de valer (una sesión activa por usuario).
- Usuario demo: `tasker` / `Demo.1234` (su `api_token` se obtiene con el login).

**Cambios que rompen compatibilidad:**
- El alta (`POST /api/v1/users`, `createUser`) exige `password` (de 8 a 72 caracteres).
- `user_name` pasa a ser el login: de 3 a 50 caracteres (letras, dígitos, punto, guion bajo y guion), único sin distinguir mayúsculas. Un `user_name` repetido responde `409`. En las cuentas existentes, los espacios del nombre se cambian por puntos.
- Las cuentas creadas antes de 1.1.0 no tienen password: siguen entrando con su `api_token`, pero no pueden hacer login.
- El `api_token` fijo del usuario demo (`apikey-demo-1234`) deja de documentarse: cambia con cada login.

## 1.0.0
- Fecha: 2026-10-24

**Nuevo:**
- **GraphQL** en `POST /graphql` y **API REST** de usuarios (`/api/v1/users`) y tareas (`/api/v1/tasks`, `/api/v1/users/<user_uuid>/tasks`), sobre los mismos casos de uso.
- **Autenticación por usuario**: `Authorization: Bearer <api_token>` en toda la API salvo el alta y la documentación.
  - El alta (`POST /api/v1/users`) devuelve el `api_token` una única vez.
  - `POST /api/v1/users/<user_uuid>/api-token` regenera el token (el anterior deja de valer).
- **Cada token solo ve lo suyo**: su usuario y sus tareas; lo ajeno responde `404`.
- Envelope único de respuesta REST `{code, status, errors, data}`.
- **Listados de tareas de la más reciente a la más antigua**, con `task_id` (solo informativo) en cada tarea.
- **GraphQL: listados paginados y filtrables**: `tasks`, `userTasks` y `User.tasks` aceptan `filter` (texto, estado, tipo, rango de fecha objetivo), `page` y `page_size`, y devuelven un `TaskPage` (`items`, `page`, `page_size`, `total_items`, `total_pages`).
- **Documentación** publicada en `/api/v1/documentation` (y este changelog).
