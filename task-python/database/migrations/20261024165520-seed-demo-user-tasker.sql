-- Migration: seed-demo-user-tasker
-- Date: 2026-10-24
-- Description: usuario demo para probar la API sin pasar por el alta.
--   user_name: tasker · user_email: tasker@tasker.loc · api_token: Demo.1234
-- Como todo token, solo se guarda su SHA-256: sha256("Demo.1234").
-- ⚠️ El token es publico (va en la documentacion): en un entorno real hay que borrar este usuario
-- o rotarle el token (POST /api/v1/users/<user_uuid>/api-token).
-- OR IGNORE: si ya existe (mismo uuid o email) no falla la migracion.

INSERT OR IGNORE INTO `users`
    (user_uuid, user_name, user_email, api_token_hash, created_at, updated_at)
VALUES
    (
        '6e142a62-5af7-4d43-b786-cec6ac17be62',
        'tasker',
        'tasker@tasker.loc',
        'e84925d5bbf23614c3fc351a190b3b8069f3eea9040ae6acd5c5b18515c46d8b',
        '2026-10-24 16:55:20',
        '2026-10-24 16:55:20'
    );
