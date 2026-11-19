-- Migration: reset-demo-user-api-token
-- Date: 2026-10-24
-- Description: nuevo api_token del usuario demo (tasker, 20261024165520-seed-demo-user-tasker.sql):
--   api_token: apikey-demo-1234  (el anterior, Demo.1234, deja de valer)
-- Como todo token, solo se guarda su SHA-256: sha256("apikey-demo-1234").
-- ⚠️ Sigue siendo publico (va en la documentacion): en un entorno real hay que borrar este usuario o rotarle el token.

UPDATE `users`
SET
    api_token_hash = '7981ceec48bca423d35b9f7edaaaa9314af5afd1c2184f6c089dd3a886865198',
    updated_at = '2026-10-24 22:29:33'
WHERE 1=1
AND user_uuid = '6e142a62-5af7-4d43-b786-cec6ac17be62';
