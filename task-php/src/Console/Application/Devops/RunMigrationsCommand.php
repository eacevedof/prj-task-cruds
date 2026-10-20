<?php
declare(strict_types=1);

namespace App\Console\Application\Devops;

use Throwable;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Devops\Application\RunMigrations\RunMigrationsDto;
use App\Modules\Devops\Application\RunMigrations\RunMigrationsService;
use App\Modules\Shared\Infrastructure\Traits\ConsoleTrait;

use App\Console\Application\IConsole;

/**
 * php console.php run-migrations   (make migrate)
 */
final class RunMigrationsCommand extends AbstractAppService implements IConsole
{
    use ConsoleTrait;

    public function __construct(array $input)
    {
        $this->input = $input;
    }

    public static function getInstance(array $input = []): self
    {
        return new self($input);
    }

    public function run(): void
    {
        $this->print("########################################### MIGRATIONS START #####################################");
        try {
            RunMigrationsService::getInstance()(RunMigrationsDto::fromPrimitives());
        }
        catch (Throwable $ex) {
            $this->print("Exception: ".$ex->getMessage());
            $this->logErr($ex->getMessage()."\n".$ex->getTraceAsString(), "RunMigrationsCommand");
        }
        $this->print("########################################### MIGRATIONS END #####################################");
    }

    private function print(string $message): void
    {
        echo $message.PHP_EOL;
        $this->logDebug($message);
    }

}
