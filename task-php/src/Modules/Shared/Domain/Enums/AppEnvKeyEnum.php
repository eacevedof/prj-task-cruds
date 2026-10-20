<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

/**
 * Claves del .env que la app consume (las que nadie lee no se mapean). Solo las usan
 * EnvironmentReaderRawRepository (un getter semántico por clave) y el loader appboot_loadenv():
 * el resto del código nunca escribe un nombre de variable de entorno.
 */
abstract class AppEnvKeyEnum
{
    public const APP_ID_CONTEXT = "APP_ID_CONTEXT";
    public const APP_ENV = "APP_ENV";
    public const APP_DEBUG = "APP_DEBUG";
    public const APP_DEFAULT_LANG = "APP_DEFAULT_LANG";
    public const APP_DB_ALIAS_1 = "APP_DB_ALIAS_1";
    public const APP_DOMAIN = "APP_DOMAIN";
    public const APP_CONTEXTS = "APP_CONTEXTS";
    public const APP_ENCDECRYPT = "APP_ENCDECRYPT";
    public const APP_SQLITE_PATH = "APP_SQLITE_PATH";
    public const APP_VAULT_LOCAL_JSON = "APP_VAULT_LOCAL_JSON";
    public const APP_VAULT_ADDR = "APP_VAULT_ADDR";
    public const APP_VAULT_TOKEN = "APP_VAULT_TOKEN";
    public const APP_EMAIL_FROM1 = "APP_EMAIL_FROM1";
    public const APP_EMAIL_TO = "APP_EMAIL_TO";
    public const APP_COOKIEID = "APP_COOKIEID";
    public const APP_EMAIL_FROM = "APP_EMAIL_FROM";
    public const APP_EMAIL_FROM_NAME = "APP_EMAIL_FROM_NAME";
    public const APP_EMAIL_DEFAULT_TPL = "APP_EMAIL_DEFAULT_TPL";
    public const APP_ENCRYPT_SALT = "APP_ENCRYPT_SALT";
    public const APP_ENCRYPT_IV = "APP_ENCRYPT_IV";
    public const APP_LOG_PATH = "APP_LOG_PATH";
    public const APP_ELASTIC_URL = "APP_ELASTIC_URL";
    public const APP_NAME = "APP_NAME";

    public const TEST_USER = "TEST_USER";
    public const TEST_PASSWORD = "TEST_PASSWORD";
}
