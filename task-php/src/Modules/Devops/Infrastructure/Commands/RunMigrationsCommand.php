<?php
declare(strict_types=1);

namespace App\Modules\Devops\Infrastructure\Commands;

use Throwable;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;

use App\Modules\Devops\Application\RunMigrations\RunMigrationsDto;
use App\Modules\Devops\Application\RunMigrations\RunMigrationsService;

/**
 * php console.php run-migrations   (make migrate)
 * Traza start / mensajes / end con logDebugCli (pantalla con color + debug.log), como los commands de app-anubis.
 * Si una migracion falla sale con codigo 2: el entrypoint del contenedor (set -e) no arranca php-fpm
 * contra una BD a medio migrar. El detalle del fallo queda en storage/logs (error.log).
 */
final class RunMigrationsCommand extends AbstractConsoleCommand
{
    public function run(): void
    {
        $this->logDebugCli("start", __METHOD__);
        try {
            $runMigrationsResultDto = RunMigrationsService::getInstance()->__invoke(RunMigrationsDto::fromPrimitives([]));
            foreach ($runMigrationsResultDto->getMessages() as $message) {
                $this->logDebugCli($message, __METHOD__);
            }
        }
        catch (Throwable $e) {
            $this->exitWithUnexpectedError($e, __METHOD__);
        }
        $this->logDebugCli("end", __METHOD__);
    }
}
