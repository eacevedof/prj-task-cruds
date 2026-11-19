-- Migration: prefix-task-uuid
-- Date: 2026-10-25
-- Description: task_uuid pasa a "task-" + uniqid() (se genera en CreateTaskService). Las existentes conservan su
--   uuid v4 detras del prefijo para que todas compartan formato. CHAR(36) no limita la longitud en SQLite.

UPDATE `tasks`
SET task_uuid = 'task-' || task_uuid
WHERE 1=1
AND task_uuid NOT LIKE 'task-%';
