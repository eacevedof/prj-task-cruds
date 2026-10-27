-- Migration: add-api-token-hash-to-users
-- Date: 2026-10-24
-- Description: token de API por usuario (Authorization: Bearer <token>). Solo se guarda su hash SHA-256:
-- el token en claro se devuelve UNA vez (alta y regeneracion) y no se puede recuperar.
-- SQLite no admite ADD COLUMN ... UNIQUE: la unicidad va en un indice aparte (NULL no colisiona).

ALTER TABLE users ADD COLUMN api_token_hash CHAR(64) NULL;

CREATE UNIQUE INDEX uk_users_api_token_hash ON users (api_token_hash);
