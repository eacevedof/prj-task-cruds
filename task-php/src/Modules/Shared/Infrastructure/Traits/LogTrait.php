<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

use App\Core\Boot\BootPathsEnum;

use App\Modules\Shared\Infrastructure\Components\Logger\Logger;

trait LogTrait
{
    protected function logSql(mixed $var, ?string $title = null): void
    {
        $this->writeAppLog("sql.sql", $var, $title);
    }

    protected function logRequest(mixed $var, ?string $title = null): void
    {
        $this->writeAppLog("request.log", $var, $title);
    }

    protected function logDebug(mixed $var, ?string $title = null): void
    {
        $this->writeAppLog("debug.log", $var, $title);
    }

    protected function logWarning(mixed $var, ?string $title = null): void
    {
        $this->writeAppLog("warning.log", $var, $title);
    }

    protected function logErr(mixed $var, ?string $title = null): void
    {
        $this->writeAppLog("error.log", $var, $title);
    }

    protected function logKafka(mixed $var, ?string $title = null): void
    {
        $this->writeAppLog("kafka.log", $var, $title);
    }

    protected function logPr(mixed $var, ?string $title = null): void
    {
        $var = print_r($var, true);
        $this->writeAppLog("debug.log", $var, $title);
        echo date("Y-m-d H:i:s");
        if ($title) {
            echo "\n$title:";
        }
        echo "\n$var\n\n";
    }

    private function writeAppLog(string $fileName, mixed $var, ?string $title): void
    {
        $content = is_string($var) ? $var : print_r($var, true);
        if ($title) {
            $content = "$title:\n$content";
        }

        Logger::getInstance()->writeLog($fileName, $content, BootPathsEnum::PATH_LOGS);
    }

}//LogTrait
