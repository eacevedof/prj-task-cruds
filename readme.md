# prj-task-cruds

To-do list CRUD en DDD con SQLite: la misma API GraphQL en varios lenguajes (**PHP es el maestro**, el resto son
réplicas 1:1) y dos fronts que pueden usar cualquiera de ellos.

## 1 - fronts

| # | Proyecto | URL |
|---|---|---|
| 1 | task-js (SPA sin build) | [http://localhost:9200](http://localhost:9200) |
| 2 | task-webassembly (la misma SPA en Go → wasm) | [http://localhost:9400](http://localhost:9400) |

Usuario de prueba: `tasker` / `Demo.1234`.

## 2 - backs

| # | Proyecto | API (health) | Documentación | Changelog | Mailpit |
|---|---|---|---|---|---|
| 3 | task-php (maestro) | [:9100/health](http://localhost:9100/health) | [:9100/api/v1](http://localhost:9100/api/v1) | [:9100/api/v1/changelog](http://localhost:9100/api/v1/changelog) | [:9125](http://localhost:9125) |
| 4 | task-go | [:9300/health](http://localhost:9300/health) | [:9300/api/v1](http://localhost:9300/api/v1) | [:9300/api/v1/changelog](http://localhost:9300/api/v1/changelog) | [:9325](http://localhost:9325) |
| 5 | task-rust | [:9500/health](http://localhost:9500/health) | [:9500/api/v1](http://localhost:9500/api/v1) | [:9500/api/v1/changelog](http://localhost:9500/api/v1/changelog) | [:9525](http://localhost:9525) |
| 6 | task-nodejs | [:9600/health](http://localhost:9600/health) | [:9600/api/v1](http://localhost:9600/api/v1) | [:9600/api/v1/changelog](http://localhost:9600/api/v1/changelog) | [:9625](http://localhost:9625) |
| 7 | task-java | [:9700/health](http://localhost:9700/health) | [:9700/api/v1](http://localhost:9700/api/v1) | [:9700/api/v1/changelog](http://localhost:9700/api/v1/changelog) | [:9725](http://localhost:9725) |
| 8 | task-python | pendiente | | | |

En todos los backs, además (solo `POST`, no se abren en el navegador):

9. `POST http://localhost:<puerto>/graphql`: la API (users y tasks), con `Authorization: Bearer <api_token>`.
10. `POST http://localhost:<puerto>/api/v1/log-in`: login con `user_name` + `password`; devuelve el `api_token`.

## 3 - arranque

Cada proyecto se levanta desde su carpeta con `make start` la primera vez y `make up` después (`make help` lista
el resto). Los fronts leen las URLs de los backs de su `docker/.env` (`API_BASE_URL_<BACK>`).
