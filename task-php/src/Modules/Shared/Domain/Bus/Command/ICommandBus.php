<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Bus\Command;

use App\Modules\Shared\Domain\Bus\Command\ICommandHandler;
use App\Modules\Shared\Domain\Bus\Command\ICommand;

interface ICommandBus
{
    public function subscribe(string $command, ICommandHandler $handler): void;

    public function publish(ICommand $command);
}
