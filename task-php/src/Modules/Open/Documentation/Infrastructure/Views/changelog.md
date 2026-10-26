# Task PHP CRUD API - changelog

⬅️ [back to the documentation]({{appBaseUrl}}/api/v1)

## 2.0.0
- Date: 2026-10-25

**Breaking changes:**
- **The users and tasks REST API is removed** (`/api/v1/users…`, `/api/v1/tasks…`): all its operations are still available in `POST /graphql` (equivalence table in the documentation, 1.1).
- `POST /api/v1/log-in`, the documentation and `GET /health` remain.

## 1.2.0
- Date: 2026-10-25

**Breaking changes:**
- Creating a task moves to `POST /api/v1/tasks` (before `POST /api/v1/users/<user_uuid>/tasks`): the task always belongs to the user of the `api_token`. The `createTask` mutation loses the `user_uuid` argument.
- `task_uuid` now has the format `task-` + a unique id (e.g. `task-671b9c4e2a1f3`). Existing tasks keep their uuid with the prefix in front.

## 1.1.0
- Date: 2026-10-25

**New:**
- **Log-in with `user_name` and `password`**: `POST /api/v1/log-in` and the `logIn` mutation, without a token. They return the user and a new `api_token`; the previous one stops working (one active session per user).
- Demo user: `tasker` / `Demo.1234` (its `api_token` is obtained with the log-in).

**Breaking changes:**
- Sign-up (`POST /api/v1/users`, `createUser`) requires `password` (8 to 72 characters).
- `user_name` becomes the log-in: 3 to 50 characters (letters, digits, dot, underscore and hyphen), unique case-insensitively. A repeated `user_name` responds `409`. In existing accounts, spaces in the name are replaced by dots.
- Accounts created before 1.1.0 have no password: they still get in with their `api_token`, but cannot log in.
- The fixed `api_token` of the demo user (`apikey-demo-1234`) is no longer documented: it changes with every log-in.

## 1.0.0
- Date: 2026-10-24

**New:**
- **GraphQL** in `POST /graphql` and a **REST API** for users (`/api/v1/users`) and tasks (`/api/v1/tasks`, `/api/v1/users/<user_uuid>/tasks`), on the same use cases.
- **Per-user authentication**: `Authorization: Bearer <api_token>` across the whole API except sign-up and the documentation.
  - Sign-up (`POST /api/v1/users`) returns the `api_token` only once.
  - `POST /api/v1/users/<user_uuid>/api-token` regenerates the token (the previous one stops working).
- **Each token only sees its own**: its user and its tasks; anything else responds `404`.
- Single REST response envelope `{code, status, errors, data}`.
- **Task lists from newest to oldest**, with `task_id` (only informative) in each task.
- **GraphQL: paginated and filterable lists**: `tasks`, `userTasks` and `User.tasks` accept `filter` (text, status, type, target date range), `page` and `page_size`, and return a `TaskPage` (`items`, `page`, `page_size`, `total_items`, `total_pages`).
- **Documentation** published in `/api/v1/documentation` (and this changelog).
