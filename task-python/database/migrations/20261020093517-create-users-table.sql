-- Migration: create-users-table
-- Date: 2026-10-20
-- Description: usuarios. 1 usuario -> n tasks (ver create-tasks-table).

CREATE TABLE users
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_uuid CHAR(36) NOT NULL UNIQUE,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(150) NOT NULL UNIQUE COLLATE NOCASE,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
