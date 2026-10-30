<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Repositories\Configuration;

use App\Modules\Shared\Domain\Enums\AppEnvKeyEnum;

use App\Core\Boot\ENV;

/**
 * Fuente ÚNICA de lectura de entorno: un getter semántico por cada clave del .env que la app consume
 * (claves en AppEnvKeyEnum; las que nadie lee no se mapean). Patrón getInstance singleton (ver
 * plataformabase EnvironmentRawRepository). El .env lo carga appboot_loadenv() (Core/config/global.php)
 * en $_ENV antes del primer getInstance(); las vars REALES del contenedor/OS que no estén en $_ENV se
 * leen con getenv() (fpm y CLI).
 */
final class EnvironmentReaderRawRepository
{
    // version publicada en /api/v1: es la del CONTRATO (la copian las replicas). Al subirla, anotar el cambio en
    // changelog.md
    private const string APP_VERSION = "2.0.1";
    private const string APP_VERSION_UPDATE = "2026-10-29";

    private array $envVars;
    private static ?self $instance = null;

    private function __construct()
    {
        $this->envVars = $_ENV;
    }

    public static function getInstance(): self
    {
        if (self::$instance) return self::$instance;

        self::$instance = new self();
        return self::$instance;
    }

    public function getAppVersion(): string
    {
        return self::APP_VERSION;
    }

    public function getAppVersionUpdate(): string
    {
        return self::APP_VERSION_UPDATE;
    }

    public function getIdContext(): string
    {
        return $this->get(AppEnvKeyEnum::APP_ID_CONTEXT);
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->get(AppEnvKeyEnum::APP_BASE_URL), "/");
    }

    public function getContextsPath(): string
    {
        return $this->get(AppEnvKeyEnum::APP_CONTEXTS);
    }

    public function getEmailTo(): string
    {
        return $this->get(AppEnvKeyEnum::APP_EMAIL_TO);
    }

    public function getEmailFrom(): string
    {
        return $this->get(AppEnvKeyEnum::APP_EMAIL_FROM);
    }

    public function getLogPath(): string
    {
        return $this->get(AppEnvKeyEnum::APP_LOG_PATH);
    }

    public function getAppName(): string
    {
        return $this->get(AppEnvKeyEnum::APP_NAME);
    }

    public function getEnvironment(): string
    {
        return $this->get(AppEnvKeyEnum::APP_ENV);
    }

    public function isProduction(): bool
    {
        return $this->getEnvironment() === ENV::PRODUCTION;
    }

    public function isDebug(): bool
    {
        //antes (bool) del string: "false" contaba como debug activo
        return filter_var($this->get(AppEnvKeyEnum::APP_DEBUG), FILTER_VALIDATE_BOOLEAN);
    }

    public function getDbAlias1(): string
    {
        return $this->get(AppEnvKeyEnum::APP_DB_ALIAS_1);
    }

    public function isLocal(): bool
    {
        return $this->getEnvironment() === ENV::LOCAL;
    }

    public function getSqlitePath(): string
    {
        return $this->get(AppEnvKeyEnum::APP_SQLITE_PATH);
    }

    public function isDev(): bool
    {
        return $this->getEnvironment() === ENV::DEVELOP;
    }

    public function isTest(): bool
    {
        return $this->getEnvironment() === ENV::TEST;
    }

    public function getColor(): string
    {
        return ENV::COLORS[$this->getEnvironment()] ?? "";
    }

    public function getDefaultLang(): string
    {
        return $this->get(AppEnvKeyEnum::APP_DEFAULT_LANG);
    }

    public function getDomain(): string
    {
        return $this->get(AppEnvKeyEnum::APP_DOMAIN);
    }

    public function getEncDecryptPath(): string
    {
        return $this->get(AppEnvKeyEnum::APP_ENCDECRYPT);
    }

    public function getVaultLocalJsonPath(): string
    {
        return $this->get(AppEnvKeyEnum::APP_VAULT_LOCAL_JSON);
    }

    public function getVaultAddr(): string
    {
        return $this->get(AppEnvKeyEnum::APP_VAULT_ADDR);
    }

    public function getVaultToken(): string
    {
        return $this->get(AppEnvKeyEnum::APP_VAULT_TOKEN);
    }

    public function getEmailFrom1(): string
    {
        return $this->get(AppEnvKeyEnum::APP_EMAIL_FROM1);
    }

    public function getCookieId(): string
    {
        return $this->get(AppEnvKeyEnum::APP_COOKIEID);
    }

    public function getEmailFromName(): string
    {
        return $this->get(AppEnvKeyEnum::APP_EMAIL_FROM_NAME);
    }

    public function getEmailDefaultTpl(): string
    {
        return $this->get(AppEnvKeyEnum::APP_EMAIL_DEFAULT_TPL);
    }

    public function getEncryptSalt(): string
    {
        return $this->get(AppEnvKeyEnum::APP_ENCRYPT_SALT);
    }

    public function getEncryptIv(): string
    {
        return $this->get(AppEnvKeyEnum::APP_ENCRYPT_IV);
    }

    public function getElasticUrl(): string
    {
        return $this->get(AppEnvKeyEnum::APP_ELASTIC_URL);
    }

    public function getTestUser(): string
    {
        return $this->get(AppEnvKeyEnum::TEST_USER);
    }

    public function getTestPassword(): string
    {
        return $this->get(AppEnvKeyEnum::TEST_PASSWORD);
    }

    /**
     * Prioriza el snapshot de $_ENV (poblado por el .env loader) y cae a getenv() para las vars
     * REALES del contenedor/OS que no estén en $_ENV. Antes getEnvironment()/isDebug() leían solo
     * $_ENV: con APP_ENV real del contenedor (variables_order sin "E") isProduction() daba false.
     */
    private function get(string $key): string
    {
        if (array_key_exists($key, $this->envVars)) {
            return trim((string) $this->envVars[$key]);
        }
        $value = getenv($key);
        return $value === false ? "" : trim($value);
    }
}
