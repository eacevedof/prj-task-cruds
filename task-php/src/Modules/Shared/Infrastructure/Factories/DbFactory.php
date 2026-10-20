<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Factories;

use Exception;
use PDO;

use TheFramework\Components\Db\ComponentMysql;
use TheFramework\Components\Db\Context\ComponentContext;

use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;

final class DbFactory
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * PDO sobre el fichero SQLite de APP_SQLITE_PATH. Si el fichero no existe, SQLite lo crea
     * (lo normal en el primer make migrate).
     */
    public function getSqlitePdoByEnvConfiguration(): PDO
    {
        $pathSqlite = EnvironmentReaderRawRepository::getInstance()->getSqlitePath();
        if (!$pathSqlite) {
            throw new Exception("missing env APP_SQLITE_PATH");
        }

        $pdoSqlite = new PDO("sqlite:$pathSqlite");
        $pdoSqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdoSqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdoSqlite->exec("PRAGMA foreign_keys = ON");
        return $pdoSqlite;
    }

    public function getMysqlInstanceByEnvConfiguration(): ComponentMysql
    {
        $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
        $context = new ComponentContext(
            $environmentReaderRawRepository->getContextsPath(),
            $environmentReaderRawRepository->getIdContext()
        );
        $dbname = $context->getDbNameByAlias(
            $environmentReaderRawRepository->getDbAlias1()
        );
        $db = $this->getMysqlInstanceByConfiguredContextAndDbName($context, $dbname);
        return $db;
    }

    public function getMysqlInstanceByConfiguredContextAndDbName(
        ComponentContext $ctx,
        string $dbname = ""
    ): ComponentMysql {
        //pr($oCtx,"octx");
        $config = $ctx->getSelected();
        $config = $this->getDbConfig($config, $dbname);
        //bug($arConfig,"arconfig");die;
        if (!$config) {
            return new ComponentMysql;
        }
        $db = new ComponentMysql($config);
        return $db;
    }

    public function getPDOInstanceByConfiguredContextAndDbName(
        ComponentContext $ctx,
        string $dbname = ""
    ): ?PDO {
        $config = $ctx->getSelected();
        $config = $this->getDbConfig($config, $dbname);
        if (!$config) {
            return null;
        }

        $arconstr["mysql:host"] = $config["server"] ?? "";
        $arconstr["dbname"] = $config["database"] ?? "";
        $arconstr["port"] = $config["port"] ?? "";

        $string = "";
        foreach($arconstr as $k => $v) {
            $string .= "$k=$v;";
        }

        try {
            $pdo = new PDO(
                $string,
                $config["user"],
                $config["password"],
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        }
        catch (Exception $e)
        {
            return null;
        }
    }

    private function getDbConfig(array $config, string $dbname): array
    {
        $context = $config["ctx"] ?? [];
        if (!$context) {
            return [];
        }

        $idx = $this->getIdxInContextByDbName($context, $dbname);
        $dbconf = [
            //"type"=>$arContext["type"] ?? "",
            "server" => $context["server"] ?? "",
            "port" => $context["port"] ?? "3306",
            "database" => $context["schemas"][$idx]["database"] ?? "",
            "user" => $context["schemas"][$idx]["user"] ?? "",
            "password" => $context["schemas"][$idx]["password"] ?? ""
        ];
        //pr($arDbconf);
        return $dbconf;
    }

    private function getIdxInContextByDbName(array $context, string $dbname): ?int
    {
        if (!$dbname) {
            return 0;
        }

        $schemas = $context["schemas"] ?? [];
        if (!$schemas) {
            return null;
        }

        foreach($schemas as $i => $schema) {
            if ($schema["database"] === $dbname) {
                return $i;
            }
        }

        return null;
    }

}//DbFactory
