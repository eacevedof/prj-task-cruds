<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

use Throwable;

use App\Core\Boot\BootPathsEnum;

use App\Modules\Shared\Infrastructure\Components\HttpRequester\HttpRequester;
use App\Modules\Shared\Infrastructure\Components\Logger\Logger;

/**
 * Logs de la app en storage/logs/YYYY-MM-DD-<nivel>.log (de app-anubis, Shared/Components/Traits/LogTrait).
 * Cada entrada lleva la ip de quien la provoca (cliente HTTP, o "cli-<ip>" desde consola) y su nivel.
 * Sin el envio a Elastic del original: aqui no hay modulo Elastic.
 */
trait LogTrait
{
    protected function logDebug(mixed $mixed, string $title = ""): void
    {
        $this->writeLevelLog("debug", "DEBUG", $mixed, $title);
    }

    protected function logInfo(mixed $mixed, string $title = ""): void
    {
        $this->writeLevelLog("info", "INFO", $mixed, $title);
    }

    protected function logSql(string $sql, string $title = ""): void
    {
        $this->writeLevelLog("sql", "SQL", $sql, $title);
    }

    protected function logWarning(mixed $mixed, string $title = ""): void
    {
        $this->writeLevelLog("warning", "WARNING", $mixed, $title);
    }

    protected function logError(mixed $mixed, string $title = ""): void
    {
        $this->writeLevelLog("error", "ERROR", $mixed, $title);
    }

    /**
     * Lo que va en todo catch (Throwable): fichero, linea, codigo, mensaje y traza.
     */
    protected function logException(Throwable $throwable, string $title = "ERROR"): void
    {
        $this->writeLevelLog("error", "ERROR", implode("\n", [
            "ex file:\n\t".$throwable->getFile(),
            "ex line:\n\t".$throwable->getLine(),
            "ex code:\n\t".$throwable->getCode(),
            "ex message:\n\t".$throwable->getMessage(),
            "ex trace:\n\t".$throwable->getTraceAsString(),
        ]), $title);
    }

    /**
     * Solo en consola: al debug.log y ademas a pantalla con color (fecha, titulo, contenido).
     */
    protected function logDebugCli(mixed $mixed, string $title = ""): void
    {
        if (php_sapi_name() !== "cli") return;

        $now = date("Y-m-d H:i:s");
        $print = is_string($mixed) ? $mixed : print_r($mixed, true);
        Logger::getInstance()->writeLog("debug", "[$now][$title]\n$print", BootPathsEnum::PATH_LOGS);
        echo "\033[97m[$now]\033[0m\033[93m[$title]\033[0m\n\033[92m$print\033[0m".PHP_EOL;
    }

    private function writeLevelLog(string $fileName, string $level, mixed $mixed, string $title): void
    {
        $content = ["host_ip" => $this->getRemoteClientIP()];
        if ($title) $content[] = $title;
        $content[] = is_string($mixed) ? $mixed : print_r($mixed, true);

        Logger::getInstance()->writeLog($fileName, "[$level] ".implode("\n", $content), BootPathsEnum::PATH_LOGS);
    }

    /**
     * La misma ip que llega a los dtos (HttpRequester::getClientIp).
     */
    private function getRemoteClientIP(): string
    {
        if (php_sapi_name() === "cli")
            return "cli-".gethostbyname(gethostname());

        return HttpRequester::getInstance()->getClientIp() ?: "unknown";
    }
}
