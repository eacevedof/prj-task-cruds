<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Repositories\Common;

use App\Modules\Shared\Infrastructure\Factories\DbFactory as DbF;
use App\Modules\Shared\Infrastructure\Repositories\AbstractMysqlRepository;

final class SysFieldReaderMysqlRepository extends AbstractMysqlRepository
{
    public function __construct()
    {
        $this->componentMysql = DbF::getInstance()->getMysqlInstanceByEnvConfiguration();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    public function getSysDataByRowData(array $row): array
    {
        $sys = [];
        if (isset($row["insert_user"])) {
            $sys["insert_user"] = "";
        }
        if (isset($row["insert_platform"])) {
            $sys["insert_platform"] = "";
        }
        if (isset($row["update_user"])) {
            $sys["update_user"] = "";
        }
        if (isset($row["update_platform"])) {
            $sys["update_platform"] = "";
        }
        if (isset($row["delete_user"])) {
            $sys["delete_user"] = "";
        }
        if (isset($row["delete_platform"])) {
            $sys["delete_platform"] = "";
        }
        if (isset($row["disabled_user"])) {
            $sys["disabled_user"] = "";
        }

        if (!$sys) {
            return [];
        }

        foreach ($sys as $key => $v) {
            $sys[$key] = (strstr($key, "_user"))
                ? $this->getUserDescriptionByIdUser($row[$key])
                : $this->getPlatformDescriptionByCodeErp($row[$key]);
        }
        return $sys;
    }

    private function getUserDescriptionByIdUser(string $idUser): string
    {
        if (!$idUser) return "";
        $id = (int) $idUser;
        $sql = $this->getQueryBuilderInstance()
            ->setComment("sysfield._getUserDescriptionByIdUser")
            ->setTable("base_user as m")
            ->setGetfields(["m.description"])
            ->addAnd("m.id=$id")
            ->select()->sql()
        ;
        $r = $this->componentMysql->query($sql);
        return $r[0]["description"] ?? "";
    }

    private function getPlatformDescriptionByCodeErp(string $idPlatform): string
    {
        if (!$idPlatform) return "";
        $sql = $this->getQueryBuilderInstance()
            ->setComment("sysfield._getPlatformDescriptionByCodeErp")
            ->setTable("base_array as m")
            ->setGetfields(["m.description"])
            ->addAnd("m.code_erp=$idPlatform")
            ->addAnd("m.type='platform'")
            ->select()->sql()
        ;
        $r = $this->componentMysql->query($sql);
        return $r[0]["description"] ?? "";
    }
}//ExampleRepository
