<?php
declare(strict_types=1);

namespace App\Console\Application;

use App\Core\Boot\BootPathsEnum;

use App\Modules\Shared\Infrastructure\Commands\AbstractConsoleCommand;
use App\Modules\Shared\Infrastructure\Traits\CliColorTrait;

/**
 * php console.php help   (o sin alias)
 * Lista console/commands.php: por comando, que hace y la llamada tal cual se escribe (la clase no: es detalle interno).
 * En verde el comando, en amarillo la llamada que se copia.
 */
final class HelpCommand extends AbstractConsoleCommand
{
    use CliColorTrait;

    public function run(): void
    {
        $commands = include(BootPathsEnum::PATH_CONSOLE."/commands.php");

        $lines = [];
        $i = 1;
        foreach ($commands as $command => $conf) {
            $info = ($conf["info"] ?? "") ? "\n\t{$conf["info"]}" : "";
            $call = trim("php console.php {$command} ".($conf["example"] ?? ""));
            $lines[] = "\n($i) ".$this->getGreen($command).":{$info}\n\t".$this->getYellow($call);
            $i++;
        }
        $this->echoBlue("help menu");
        echo implode("", $lines).PHP_EOL;
    }
}
