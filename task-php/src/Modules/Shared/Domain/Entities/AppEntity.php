<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Entities;

use App\Modules\Shared\Domain\Enums\EntityEnum;
use App\Modules\Shared\Domain\Enums\PlatformEnum;

abstract class AppEntity
{
    protected array $fields;
    protected array $pks;

    protected array $sysFields = [
        EntityEnum::INSERT_DATE,
        EntityEnum::INSERT_USER,
        EntityEnum::INSERT_PLATFORM,
        EntityEnum::UPDATE_DATE,
        EntityEnum::UPDATE_USER,
        EntityEnum::UPDATE_PLATFORM,
        EntityEnum::DELETE_DATE,
        EntityEnum::DELETE_USER,
        EntityEnum::DELETE_PLATFORM,
        EntityEnum::PROCESS_FLAG,
        EntityEnum::CRU_CSVNOTE,
        EntityEnum::IS_ERP_SENT,
        EntityEnum::IS_ENABLED,
        EntityEnum::I,
    ];

    public function getFields(): array
    {
        return $this->fields;
    }
    public function getPks(): array
    {
        return $this->pks;
    }

    public function isInFields(string $fieldName): bool
    {
        if(in_array($fieldName, $this->sysFields)) {
            return true;
        }

        return (bool) ($this->fields[$fieldName] ?? false);
    }

    public function getLabel(string $fieldName): string
    {
        return $this->fields[$fieldName]["label"] ?? "";
    }

    public function getRequestKeyByFieldName(string $fieldName): string
    {
        return $this->fields[$fieldName][EntityEnum::REQUEST_KEY] ?? $fieldName;
    }

    public function getTypeByFieldName(string $fieldName): string
    {
        return $this->fields[$fieldName]["config"]["type"] ?? "";
    }

    public function getLength(string $fieldName): ?int
    {
        return $this->fields[$fieldName]["config"]["length"] ?? null;
    }

    public function getFieldNamedByRequestKey(string $requestKey): string
    {
        foreach ($this->fields as $fieldName => $array) {
            if(($array[EntityEnum::REQUEST_KEY] ?? "") === $requestKey) {
                return $fieldName;
            }
        }
        return "";
    }

    public function getAllKeyValueFromRequest(array $request): array
    {
        $nullables = [
            EntityEnum::DATE,
            EntityEnum::DATETIME,
            EntityEnum::INT,
            EntityEnum::DECIMAL
        ];

        $requestKeys = array_keys($request);
        $mapped = [];
        foreach ($requestKeys as $requestKey) {
            $dbFieldName = $this->getFieldNamedByRequestKey($requestKey);
            $dbFieldType = $this->getTypeByFieldName($dbFieldName);
            $fieldValue = "";
            if ($dbFieldName) {
                // el valor puede venir ya casteado a int (getInputWithoutOperations); trim() exige
                // string con declare(strict_types) -> normalizamos a string antes.
                $fieldValue = trim((string) ($request[$requestKey] ?? ""));
                if ($dbFieldType === EntityEnum::DATETIME) {
                    $fieldValue = $this->getDtSanitized($fieldValue);
                }
                $mapped[$dbFieldName] = $fieldValue;
            }

            if(in_array($dbFieldType, $nullables) && $fieldValue === "") {
                $mapped[$dbFieldName] = null;
            }
        }
        return $mapped;
    }

    public function areAllPksPresent(array $pkValues): bool
    {
        if (!$pkValues) {
            return false;
        }
        $pkNames = array_keys($pkValues);
        foreach ($this->pks as $pkFieldName) {
            if (!in_array($pkFieldName, $pkNames)) {
                return false;
            }
            $value = $pkValues[$pkFieldName];
            if (!$value) {
                return false;
            }
        }
        return true;
    }

    public function addSysInsert(array &$request, string|int $idUser, string $platform = PlatformEnum::WEB): self
    {
        $request[EntityEnum::INSERT_DATE] = date("Y-m-d H:i:s");
        $request[EntityEnum::INSERT_USER] = $idUser;
        $request[EntityEnum::INSERT_PLATFORM] = $platform;
        return $this;
    }
    public function addSysUpdate(array &$request, string|int $idUser, string $platform = PlatformEnum::WEB): self
    {
        $request[EntityEnum::UPDATE_DATE] = date("Y-m-d H:i:s");
        $request[EntityEnum::UPDATE_USER] = $idUser;
        $request[EntityEnum::UPDATE_PLATFORM] = $platform;
        return $this;
    }

    //string|int igual que addSysInsert/addSysUpdate: el id del usuario autenticado viaja como int
    //(UsersReaderMysqlRepository::getUserByEmail lo pasa por mapColumnsToInt) y con strict_types
    //declararlo solo string reventaba con TypeError en los tres call-sites de borrado.
    public function addSysDelete(array &$request, string $updateDate, string|int $idUser, string $platform = PlatformEnum::WEB): self
    {
        $request[EntityEnum::DELETE_DATE] = date("Y-m-d H:i:s");
        $request[EntityEnum::DELETE_USER] = $idUser;
        $request[EntityEnum::DELETE_PLATFORM] = $platform;
        $request[EntityEnum::UPDATE_DATE] = $updateDate;
        return $this;
    }

    private function getDtSanitized(string $dt): string
    {
        if (strlen($dt) == 16) {
            $dt = "$dt:00";
        }
        if (str_contains($dt, "T")) {
            $dt = str_replace("T", " ", $dt);
        }
        return $dt;
    }

}//AppEntity
