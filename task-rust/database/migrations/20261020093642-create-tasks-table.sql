-- Migration: create-tasks-table
-- Date: 2026-10-20
-- Description: tareas de un usuario. Borrar el usuario borra sus tareas (ON DELETE CASCADE,
-- requiere PRAGMA foreign_keys = ON, que activa DbFactory en cada conexion).
-- Valores permitidos: TaskStatusEnum / TaskTypeEnum (src/Modules/Tasks/Domain/Enums).

CREATE TABLE tasks
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    task_uuid CHAR(36) NOT NULL UNIQUE,
    task_title VARCHAR(150) NOT NULL,
    task_status VARCHAR(20) NOT NULL DEFAULT 'pending'
        CHECK (task_status IN ('pending', 'in_progress', 'done')),
    task_type VARCHAR(20) NOT NULL DEFAULT 'personal'
        CHECK (task_type IN ('personal', 'work', 'other')),
    target_date TEXT NULL,
    task_description TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tasks_user_id ON tasks (user_id);
