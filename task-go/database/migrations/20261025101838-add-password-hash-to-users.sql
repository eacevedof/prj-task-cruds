-- Migration: add-password-hash-to-users
-- Date: 2026-10-25
-- Description: login con user_name + password (POST /api/v1/log-in, mutation logIn).
--   1. password_hash: password_hash() de PHP (bcrypt). NULL = cuenta sin password: no puede hacer login
--      hasta que se le ponga una (las creadas antes de esta migracion).
--   2. user_name pasa a ser el identificador de login: unico sin distinguir mayusculas. Los espacios de los
--      nombres antiguos ("Ana Lopez") se cambian por puntos ("Ana.Lopez"): el login no admite espacios.
--   3. usuario demo (20261024165520-seed-demo-user-tasker.sql): user_name tasker, password Demo.1234.
-- ⚠️ La password demo es publica (va en la documentacion): en un entorno real hay que borrar este usuario.

ALTER TABLE `users` ADD COLUMN password_hash VARCHAR(255) NULL;

UPDATE `users`
SET user_name = REPLACE(TRIM(user_name), ' ', '.')
WHERE 1=1
AND user_name LIKE '% %';

UPDATE `users`
SET
    user_name = 'tasker',
    password_hash = '$2y$12$t9VmhmHODPDl59ujf00t.OVW369B9DTKq.8N9QHREe04y5uX.YJZi',
    updated_at = '2026-10-25 10:18:38'
WHERE 1=1
AND user_uuid = '6e142a62-5af7-4d43-b786-cec6ac17be62';

CREATE UNIQUE INDEX uk_users_user_name ON `users` (user_name COLLATE NOCASE);
