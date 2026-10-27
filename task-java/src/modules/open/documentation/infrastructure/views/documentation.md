# Task Java CRUD API - [{{appVersion}}]({{appBaseUrl}}/api/v1/changelog#{{appVersion}})
- Last update: {{appVersionUpdate}}
- Task list per user: the whole API runs on **GraphQL** (`POST /graphql`); log-in is also available over REST (`POST /api/v1/log-in`).

---

## 1 - GraphQL

1. A single endpoint: **POST** `/graphql` with body `{"query": "...", "variables": {...}}` and the header `Authorization: Bearer <api_token>` (see 2 - Authentication).
2. Without a token, only the mutations `createUser` (sign-up) and `logIn` (log-in) work.
3. Fields and arguments in `snake_case` (`user_uuid`, `task_title`…).
4. Errors follow the GraphQL standard: the response is `200` and the error goes in `errors[]`, with `extensions.code` = the equivalent HTTP code (see 1.6) and `extensions.errors` = the message (in a validation, with the detail of each field).
5. Maximum query depth: 5 levels.

### 1.1 Operations

"Requires token" = the header `Authorization: Bearer <api_token>` must be sent. The two public ones are precisely
the ones that give you the token: sign-up and log-in.

| # | Module | Type | Operation | Requires token | What it does |
|---|---|---|---|---|---|
| 1 | users | mutation | `createUser(user_name, user_email, password)` | no (public) | Sign-up: returns the user and their `api_token` (see 4.1) |
| 2 | users | mutation | `logIn(user_name, password)` | no (public) | Log-in: returns the user and a new `api_token` (see 4.2) |
| 3 | users | query | `users` | yes | List with your user (at most one item) |
| 4 | users | query | `user(user_uuid)` | yes | Your user by its uuid |
| 5 | users | mutation | `updateUser(user_uuid, user_name, user_email)` | yes | Full replacement of the user (see 4.3) |
| 6 | users | mutation | `regenerateApiToken(user_uuid)` | yes | New token; the previous one stops working (see 4.4) |
| 7 | users | mutation | `deleteUser(user_uuid)` | yes | Deletes the user and all their tasks (see 4.5) |
| 8 | tasks | mutation | `createTask(task_title, ...)` | yes | Creates a task of yours (see 5.2) |
| 9 | tasks | query | `tasks(filter, page, page_size)` | yes | Your tasks, paginated and filterable (see 1.2) |
| 10 | tasks | query | `task(task_uuid)` | yes | One task |
| 11 | tasks | query | `userTasks(user_uuid, filter, page, page_size)` | yes | Your user's tasks, paginated and filterable |
| 12 | tasks | mutation | `updateTask(task_uuid, task_title, ...)` | yes | Full replacement of the task (see 5.3) |
| 13 | tasks | mutation | `deleteTask(task_uuid)` | yes | Deletes the task (see 5.4) |

Also, the `User` type has the field `tasks(filter, page, page_size)`: user and tasks in a single query.

### 1.2 Task lists: pagination and filters

`tasks`, `userTasks` and `User.tasks` return a page (`TaskPage`), always from newest to oldest (`task_id` descending).

| # | Argument | Type | What it does |
|---|---|---|---|
| 1 | `filter.task_text` | String | Contained in the title or the description, case-insensitive |
| 2 | `filter.task_status` | String | `pending` · `in_progress` · `done` |
| 3 | `filter.task_type` | String | `personal` · `work` · `other` |
| 4 | `filter.target_date_from` | String | `YYYY-MM-DD`, inclusive (a task without a target date is left out) |
| 5 | `filter.target_date_to` | String | `YYYY-MM-DD`, inclusive; cannot be earlier than `target_date_from` |
| 6 | `page` | Int | From 1 (default 1) |
| 7 | `page_size` | Int | From 1 to 100 (default 20) |

The response: `items` (the tasks of the page), `page`, `page_size`, `total_items` (all the ones matching the filters) and `total_pages`. An out-of-range argument responds with `extensions.code: 400` and a message with the detail of each field.

```
curl -X POST '{{appBaseUrl}}/graphql' --header 'Authorization: Bearer <api_token>' --header 'Content-Type: application/json' --data '{"query": "query($filter: TasksFilter, $page: Int, $pageSize: Int) { tasks(filter: $filter, page: $page, page_size: $pageSize) { items { task_id task_title target_date } page page_size total_items total_pages } }", "variables": {"filter": {"task_text": "demo", "target_date_from": "2026-12-01"}, "page": 1, "pageSize": 10}}'
```

#### response:
```
{
    "data": {
        "tasks": {
            "items": [
                {"task_id": 42, "task_title": "Prepare the demo", "target_date": "2026-12-31"}
            ],
            "page": 1,
            "page_size": 10,
            "total_items": 1,
            "total_pages": 1
        }
    }
}
```

### 1.3 Query

```
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "{ users { user_uuid user_name tasks(page_size: 5) { items { task_title task_status } total_items } } }"}'
```

#### response:
```
{
    "data": {
        "users": [
            {
                "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
                "user_name": "ana.lopez",
                "tasks": {
                    "items": [
                        {"task_title": "Prepare the demo", "task_status": "pending"}
                    ],
                    "total_items": 1
                }
            }
        ]
    }
}
```

### 1.4 Mutation with variables

```
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "mutation($id: ID!) { updateTask(task_uuid: $id, task_title: \"Prepare the demo\", task_status: \"done\") { task_uuid task_status } }", "variables": {"id": "<task_uuid>"}}'
```

### 1.5 Error

```
{
    "errors": [
        {
            "message": "Task task-671b9c4e2a1f3 not found",
            "path": ["task"],
            "extensions": {"code": 404, "errors": ["Task task-671b9c4e2a1f3 not found"]}
        }
    ],
    "data": {"task": null}
}
```

### 1.6 Error codes (`extensions.code`)

| # | Code | When |
|---|---|---|
| 1 | `400` | Validation: a single message with the detail of each field (`"Fields validation errors: user_name: field is required; user_email: invalid email"`) |
| 2 | `401` | The token is missing or not valid, or wrong `user_name`/`password` in the log-in (same message in both cases) |
| 3 | `404` | The resource does not exist or is not yours |
| 4 | `409` | Conflict: the `user_name` or the email is already in use |
| 5 | `500` | Unexpected error (logged on the server; the message gives no details) |

A syntax error or a field that does not exist in the schema has no `extensions.code`: it is a GraphQL error, not an application one.

---

## 2 - Authentication

1. Each user has their own **api_token**. It is sent in every request with the header `Authorization: Bearer <api_token>`.
2. The token is obtained when **signing up** (`createUser`) or when **logging in** with `user_name` and `password` (`logIn` or `POST /api/v1/log-in`), without a token. It is returned **only once**: the server only stores its hash and cannot recover it.
3. **Every log-in generates a new token and the previous one stops working**: a user has a single active session (logging in from somewhere else closes the previous one).
4. If the token leaks, it is **regenerated** with `regenerateApiToken` (or with a log-in): the previous one stops working immediately.
5. Each token only sees and changes **its own user and their tasks**. A resource of another user responds `404`, the same as if it did not exist.
6. Always use HTTPS outside local: the token and the password travel in the request.

### 2.1 Quick flow

```
# 1. log-in: returns the user and a new api_token
curl -X POST '{{appBaseUrl}}/api/v1/log-in' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "password": "<password>"}'

# 2. with the token, create a task
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "mutation { createTask(task_title: \"Buy bread\") { task_uuid task_title } }"}'
```

### 2.2 Demo user

To try it without signing up: log in with it and use the `api_token` it returns.

| # | Field | Value |
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

It is a shared user: every log-in gives it a new token, so someone else's stops working.

### 2.3 Optional headers

They come with every request and the server logs them as context of the operation.

| # | Header | Example | If missing or not valid |
|---|---|---|---|
| 1 | `User-Agent` | `Mozilla/5.0 ... Chrome/141.0.0.0` | Empty browser and operating system |
| 2 | `Accept-Language` | `es-ES,es;q=0.9` | `en` (languages: `es`, `en`) |
| 3 | `X-Time-Zone` | `Europe/Madrid` (IANA zone) | Empty |

---

## 3 - REST log-in

- **POST** `/api/v1/log-in` · without a token. Body: `user_name` (case-insensitive) · `password`.
- It is the only REST endpoint: it does the same as the `logIn` mutation.
- `200` response: the user and a **new** `api_token`; the previous one stops working.
- Unknown `user_name`, account without a password or wrong password: `401` with the same message (`Invalid user name or password`), so as not to reveal which users exist.

```
curl -X POST '{{appBaseUrl}}/api/v1/log-in' \
--header 'Content-Type: application/json' \
--data '{"user_name": "ana.lopez", "password": "<password>"}'
```

#### response:
```
{
    "code": 200,
    "status": true,
    "errors": [],
    "data": {
        "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
        "user_name": "ana.lopez",
        "user_email": "ana@example.com",
        "created_at": "2026-10-24 10:00:00",
        "updated_at": "2026-10-25 09:30:00",
        "api_token": "9b1f...64 hex characters...c0de"
    }
}
```

An error follows the same format, with `status: false` and the message in `errors`. A method other than `POST` responds `405` with the `Allow` header.

---

## 4 - Users

### 4.1 Sign-up (`createUser`, without a token)
- `user_name` (required) is the log-in: 3 to 50 characters, only letters, digits, dot, underscore and hyphen; unique case-insensitively (`Ana` and `ana` are the same).
- `user_email` (required, max. 150, unique).
- `password` (required): 8 to 72 characters. Only its hash is stored (bcrypt).
- Returns the user and their `api_token`.

```
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Content-Type: application/json' \
--data '{"query": "mutation($n: String!, $e: String!, $p: String!) { createUser(user_name: $n, user_email: $e, password: $p) { user_uuid user_name api_token } }", "variables": {"n": "ana.lopez", "e": "ana@example.com", "p": "<password>"}}'
```

### 4.2 Log-in (`logIn`, without a token)
- Same rules as the REST log-in (see 3): returns the user with a new `api_token`.

### 4.3 Update (`updateUser`)
- Full replacement: `user_name` and `user_email` required, with the same rules as the sign-up. The password is not changed here.

### 4.4 Regenerate the token (`regenerateApiToken`)
- Returns `{"user_uuid": "...", "api_token": "..."}` with a new token; the previous one stops working immediately.

### 4.5 Delete (`deleteUser`)
- Physical deletion of the user and all their tasks. Returns `{"user_uuid": "...", "affected": 1}`.

---

## 5 - Tasks

### 5.1 Task fields

| # | Field | Type | Rules |
|---|---|---|---|
| 1 | `task_title` | string | Required, max. 150 |
| 2 | `task_status` | string | `pending` · `in_progress` · `done` (default `pending`) |
| 3 | `task_type` | string | `personal` · `work` · `other` (default `personal`) |
| 4 | `target_date` | string | Optional, a real date `YYYY-MM-DD` |
| 5 | `task_description` | string | Optional, max. 2000 |

`task_id` is only informative (ordering, readable reference): to read, update or delete, `task_uuid` is used.

### 5.2 Create (`createTask`)
- The task belongs to the user of the `api_token`. The `task_uuid` is generated by the API: `task-` + a unique id.

```
curl -X POST '{{appBaseUrl}}/graphql' \
--header 'Authorization: Bearer <api_token>' \
--header 'Content-Type: application/json' \
--data '{"query": "mutation { createTask(task_title: \"Prepare the demo\", task_type: \"work\", target_date: \"2026-12-31\") { task_id task_uuid user_uuid task_title task_status task_type target_date } }"}'
```

#### response:
```
{
    "data": {
        "createTask": {
            "task_id": 42,
            "task_uuid": "task-671b9c4e2a1f3",
            "user_uuid": "0f8c3a52-6c1e-4a57-9a3e-2b7d1c9e4f10",
            "task_title": "Prepare the demo",
            "task_status": "pending",
            "task_type": "work",
            "target_date": "2026-12-31"
        }
    }
}
```

### 5.3 Update (`updateTask`)
- Full replacement with the fields of 5.1: what is not sent goes back to its default value or is left empty.

### 5.4 Delete (`deleteTask`)
- Returns `{"task_uuid": "...", "affected": 1}`.
