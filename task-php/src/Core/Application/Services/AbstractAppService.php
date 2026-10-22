<?php
declare(strict_types=1);

namespace App\Core\Application\Services;

use Exception;

use TheFramework\Components\Config\ComponentConfig;
use TheFramework\Components\Session\ComponentEncDecrypt;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\Date\UtcComponent;
use App\Modules\Shared\Infrastructure\Repositories\Common\SysFieldReaderMysqlRepository;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;
use App\Modules\Shared\Infrastructure\Traits\ErrorTrait;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;
use App\Modules\Shared\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Shared\Infrastructure\Exceptions\ForbiddenException;
use App\Modules\Shared\Infrastructure\Exceptions\NotFoundException;

abstract class AbstractAppService
{
    use ErrorTrait, LogTrait;

    protected mixed $input;

    protected function throwException(string $message, int $code = ExceptionEnum::CODE_INTERNAL_SERVER_ERROR): void
    {
        $this->logErr($message, "app-service.exception");
        switch ($code) {
            case ExceptionEnum::CODE_BAD_REQUEST: BadRequestException::badRequestCustom($message);
            case ExceptionEnum::CODE_FORBIDDEN: ForbiddenException::forbiddenCustom($message);
            case ExceptionEnum::CODE_NOT_FOUND: NotFoundException::notFoundCustom($message);
        }
        // fallback generico (5xx u otros codigos sin excepcion tipada)
        throw new Exception($message, $code);
    }

    /**
     * created_at / updated_at: siempre UTC, formato "Y-m-d H:i:s" (el de CURRENT_TIMESTAMP de SQLite).
     */
    protected function getUtcNow(): string
    {
        return gmdate("Y-m-d H:i:s");
    }

    protected function getEncDecryptInstance(): ComponentEncDecrypt
    {
        $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
        $pathFile = $environmentReaderRawRepository->getEncDecryptPath() ?: __DIR__.DIRECTORY_SEPARATOR."encdecrypt.json";
        $appDomain = $environmentReaderRawRepository->getDomain();
        $config = (new ComponentConfig($pathFile))->getNode("domain", $appDomain);
        if (!$config) {
            $this->throwException("Domain {$appDomain} is not authorized");
        }

        $encDecrypt = ComponentEncDecrypt::getInstance();
        $encDecrypt->setSslMethod($config["sslenc_method"] ?? "");
        $encDecrypt->setSslKey($config["sslenc_key"] ?? "");
        $encDecrypt->setSaltString($config["sslsalt"] ?? "");
        return $encDecrypt;
    }

    protected function getRowWithSysDataByTz(array $row, string $tz = UtcComponent::TZ_UTC): array
    {
        $dateFields = [
            "insert_date",
            "update_date",
            "delete_date",
        ];
        $utc = UtcComponent::getInstance();
        foreach($row as $field => $value) {
            if(in_array($field, $dateFields) && $value) {
                $row[$field] = $utc->getUtcDtInTargetTz($value, $tz);
            }
        }

        $sysData = SysFieldReaderMysqlRepository::getInstance()->getSysDataByRowData($row);
        return array_merge($row, $sysData);
    }

    /**
     * Ultimo filtro antes de la entidad para los formularios: quita las claves de operacion ("_xxx"),
     * "" (o solo espacios) -> null y castea a int las FK "id_" numericas. Puro: solo trabaja con el
     * input del DTO (antes vivia en RequestTrait y, con el array vacio, leia $_POST).
     */
    protected function getInputWithoutOperations(array $input): array
    {
        $without = [];
        foreach ($input as $key => $value) {
            if (str_starts_with((string) $key, "_")) {
                continue;
            }

            $tmpValue = is_string($value) && trim($value) === ""
                            ? null
                            : trim((string) ($value ?? ""))
            ;
            //PREFIJO, no "contiene": con strstr() una clave tipo valid_from o uuid_ref (que
            //llevan "id_" dentro y no son FK) perdia su forma de string al castearse a int.
            if (str_starts_with((string) $key, "id_") && is_numeric($value)) {
                $tmpValue = (int) $value;
            }
            $without[$key] = $tmpValue;
        }
        return $without;
    }

}//AbstractAppService
