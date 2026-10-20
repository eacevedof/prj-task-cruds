<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Enums;

/**
 * Claves de la API (JSON de entrada y salida, argumentos GraphQL) de users.
 */
abstract class UsersKeyEnum
{
    public const string USER_UUID = "user_uuid";
    public const string USER_NAME = "user_name";
    public const string USER_EMAIL = "user_email";
    public const string CREATED_AT = "created_at";
    public const string UPDATED_AT = "updated_at";
    public const string API_TOKEN = "api_token";
    public const string PASSWORD = "password";
    public const string AFFECTED = "affected";
}
