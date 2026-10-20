<?php
declare(strict_types=1);

namespace App\Console\Application;

use App\Core\Application\Services\AbstractAppService;
use App\Core\Boot\BootPathsEnum;

use App\Modules\Shared\Infrastructure\Traits\ConsoleTrait;

final class HelpService extends AbstractAppService implements IConsole
{
    use ConsoleTrait;

    private array $commands;

    public function __construct(array $input)
    {
        $this->input = $input;
        $pathFileServices = BootPathsEnum::PATH_CONSOLE."/commands.php";
        $this->commands = include($pathFileServices);
    }

    public static function getInstance(array $input = []): self
    {
        return new self($input);
    }

    public function run(): void
    {
        $lines = [];
        $i = 1;
        foreach($this->commands as $command => $conf) {
            $service = $conf["service"];
            $info = ($conf["info"] ?? "") ? "\n\t{$conf["info"]}" : "";
            $lines[] = "\n($i) $command:\n\t{$service}{$info}";
            $i++;
        }
        $message = implode("", $lines)."\n";
        $this->pr($message, "help menu");
    }

}
