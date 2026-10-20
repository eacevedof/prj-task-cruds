<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Repositories;

use TheFramework\Components\Db\ComponentMysql;
use TheFramework\Components\Db\ComponentQB;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Domain\Entities\AppEntity;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;
use App\Modules\Shared\Infrastructure\Exceptions\RepositoryException;

abstract class AbstractMysqlRepository
{
    use LogTrait;

    protected ?AppEntity $appEntity = null;
    protected ?ComponentMysql $componentMysql = null;
    protected string $table = "";

    public function setAppEntity(AppEntity $appEntity): self
    {
        $this->appEntity = $appEntity;
        return $this;
    }

    public function query(
        string $sql,
        ?int $col = null, ?int $row = null
    ): array|string|null
    {
        return $this->componentMysql->query($sql, $col, $row);
    }

    public function getQueryWithCount(string $sqlCount, ?string $sql): array
    {
        return [
            "result" => $this->componentMysql->setSqlcount($sqlCount)->query($sql),
            "total" => $this->componentMysql->getFoundrows(),
        ];
    }

    public function execute(?string $sql): int|false
    {
        return $this->componentMysql->exec($sql);
    }

    public function insert(array $request): int
    {
        $qb = $this->getQueryBuilderInstance()
            ->setComment("apprepository.insert")
            ->setDb($this->componentMysql)
            ->setTable($this->table);

        foreach($request as $field => $value) {
            if (!$this->fieldInEntityFields($field)) {
                continue;
            }
            $qb->addInsertFv($field, $value);
        }

        $qb->insert()->exec($qb::WRITE);
        return $this->componentMysql->getLastid();
    }//insert

    public function update(array $request): int
    {
        $pks = $this->getPkFieldsAndValues($request);
        if (!$pks) {
            $this->throwRepositoryException(__(SharedMessageEnum::NO_CODE_S_PROVIDED), ExceptionEnum::CODE_UNPROCESSABLE_ENTITY);
        }

        $mutables = $this->getNoPkFieldsAndValues($request);
        if (!$mutables) {
            $this->throwRepositoryException(__(SharedMessageEnum::NO_DATA_TO_UPDATE), ExceptionEnum::CODE_UNPROCESSABLE_ENTITY);
        }

        $qb = $this->getQueryBuilderInstance()
            ->setComment("apprepository.update")
            ->setDb($this->componentMysql)
            ->setTable($this->table);

        //valores del "SET"
        foreach($mutables as $fieldName => $value) {
            if (!$this->fieldInEntityFields($fieldName)) {
                continue;
            }
            $qb->addUpdateFv($fieldName, $value);
        }

        //valores del WHERE
        foreach($pks as $fieldName => $value) {
            if (!$this->fieldInEntityFields($fieldName)) {
                continue;
            }
            $qb->addPkFv($fieldName, $value);
        }

        $qb->update()->exec($qb::WRITE);
        return $this->componentMysql->getAffected();

    }//update

    public function delete(array $request): int
    {
        $pks = $this->getPkFieldsAndValues($request);
        if (!$pks) {
            $this->throwRepositoryException(__(SharedMessageEnum::NO_CODE_S_PROVIDED), ExceptionEnum::CODE_UNPROCESSABLE_ENTITY);
        }

        $qb = $this->getQueryBuilderInstance()
            ->setComment("apprepository.delete")
            ->setDb($this->componentMysql)
            ->setTable($this->table);

        //valores del WHERE
        foreach($pks as $fieldName => $value) {
            if (!$this->fieldInEntityFields($fieldName)) {
                continue;
            }
            $qb->addPkFv($fieldName, $value);
        }

        $qb->delete()->exec($qb::WRITE);
        return $this->componentMysql->getAffected();
    }//delete

    public function getSysUpdateDateByPkFields(array $pkFieldsAndValues): string
    {
        if (!$pkFieldsAndValues) return "";

        $qb = $this->getQueryBuilderInstance()
            ->setComment("apprepository.get_sysupdate")
            ->setDb($this->componentMysql)
            ->setGetfields(["m.update_date"])
            ->setTable("$this->table as m")
        ;

        foreach($pkFieldsAndValues as $fieldName => $value) {
            $qb->addPkFv($fieldName, $value);
        }

        $sql = $qb->select()->sql();
        $r = $this->componentMysql->query($sql);
        $this->mapColumnsToInt($r, ["id"]);
        return $r[0]["update_date"] ?? "";
    }

    public function getCsvCru(array $row, string $idUser): string
    {
        $now = date("Y-m-d H:i:s");
        $crucsv = $row["cru_csvnote"] ?? "";
        $crucsv = "delete_user:{$row["delete_user"]},delete_date:{$row["delete_date"]},delete_platform:{$row["delete_platform"]},($idUser:$now)|".$crucsv;
        return substr($crucsv, 0, 499);
    }

    public function getEntityIdByEntityUuid(string $entityUuid): int
    {
        $entityUuid = $this->getSanitizedString($entityUuid);
        $sql = $this->getQueryBuilderInstance()
            ->setComment("apprepository.get_id_by_uuid")
            ->setTable("$this->table as m")
            ->setGetfields(["m.id"])
            ->addAnd("m.uuid='$entityUuid'")
            ->select()->sql()
        ;
        $r = $this->componentMysql->query($sql);
        $this->mapColumnsToInt($r, ["id"]);
        return $r[0]["id"] ?? 0;
    }

    public function getEntityByEntityId(string|int $entityId, array $fields = []): array
    {
        $entityId = (int) $entityId;
        $sql = $this->getQueryBuilderInstance()
            ->setComment("apprepository.get_by_id")
            ->setTable("$this->table as m")
            ->setGetfields(["m.*"])
            ->addAnd("m.id=$entityId")
        ;
        if ($fields) {
            $sql->setGetfields($fields);
        }
        $sql = $sql->select()->sql();

        $r = $this->componentMysql->query($sql);
        $this->mapColumnsToInt($r, ["id"]);
        return $r[0] ?? [];

    }

    public function getEntityByEntityUuid(string $entityUuid, array $fields = []): array
    {
        $entityUuid = $this->getSanitizedString($entityUuid);
        $sql = $this->getQueryBuilderInstance()
            ->setComment("apprepository.get_by_uuid")
            ->setTable("$this->table as m")
            ->setGetfields(["m.*"])
            ->addAnd("m.uuid='$entityUuid'")
        ;
        if ($fields) {
            $sql->setGetfields($fields);
        }

        $sql = $sql->select()->sql();
        $r = $this->componentMysql->query($sql);
        //@eaftodo aqui hay que incluir el resto de ids que sean enteros
        $this->mapColumnsToInt($r, ["id"]);
        return $r[0] ?? [];

    }

    public function isDeletedByEntityId(int|string $entityId): bool
    {
        $entityId = (int) $entityId;
        $sql = $this->getQueryBuilderInstance()
            ->setComment("apprepository.is_deleted")
            ->setTable("$this->table as m")
            ->setGetfields(["m.delete_date"])
            ->addAnd("m.id=$entityId")
            ->select()->sql()
        ;
        $r = $this->componentMysql->query($sql);
        $this->mapColumnsToInt($r, ["id"]);
        return (bool) ($r[0]["delete_date"] ?? "");
    }

    protected function getSanitizedString(string $value): string
    {
        return $this->getQueryBuilderInstance()->getSanitizedString($value);
    }

    protected function getQueryBuilderInstance(): ComponentQB
    {
        return new ComponentQB;
    }

    protected function throwRepositoryException(
        string $message,
        int $code = ExceptionEnum::CODE_REQUESTED_RANGE_NOT_SATISFIABLE
    ): void {
        throw new RepositoryException($message, $code);
    }

    protected function mapColumnsToInt(array &$rows, array $fields): void
    {
        $this->mapColumns($rows, $fields, static fn ($value): int => (int) $value);
    }

    protected function mapColumnsToFloat(array &$rows, array $fields): void
    {
        // Los decimales nullables (importes, ratios) se dejan null; el resto a float.
        $this->mapColumns($rows, $fields, static fn ($value): ?float => $value === null ? null : (float) $value);
    }

    protected function mapColumnsToBool(array &$rows, array $fields): void
    {
        $this->mapColumns($rows, $fields, static fn ($value): bool => (bool) $value);
    }

    protected function mapJsonColumnsToArray(array &$rows, array $fields): void
    {
        $this->mapColumns($rows, $fields, static function ($value): array {
            $decoded = $value ? json_decode((string) $value, true) : [];
            return is_array($decoded) ? $decoded : [];
        });
    }

    private function getPkFieldsAndValues(array $data): array
    {
        $pks = [];
        foreach($data as $fieldName => $value) {
            if(in_array($fieldName, $this->appEntity->getPks())) {
                $pks[$fieldName] = $value;
            }
        }
        return $pks;
    }

    private function getNoPkFieldsAndValues(array $data): array
    {
        $pks = [];
        foreach($data as $fieldName => $value) {
            if (!in_array($fieldName, $this->appEntity->getPks())) {
                $pks[$fieldName] = $value;
            }
        }
        return $pks;
    }

    private function fieldInEntityFields(string $fieldName): bool
    {
        if (!$this->appEntity) return true;
        return $this->appEntity->isInFields($fieldName);
    }

    private function mapColumns(array &$rows, array $fields, callable $caster): void
    {
        if (!$rows || !$fields) return;

        foreach ($rows as $i => $row) {
            foreach ($fields as $field) {
                if (!array_key_exists($field, $row)) {
                    continue;
                }
                $rows[$i][$field] = $caster($row[$field]);
            }
        }
    }

}//AbstractMysqlRepository
