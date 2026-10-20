<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Bus\Command;

use App\Modules\Shared\Domain\Bus\Command\InterfaceCommandHandler;
use App\Modules\Shared\Domain\Bus\Command\InterfaceCommand;

interface InterfaceCommandBus
{
    public function subscribe(string $command, InterfaceCommandHandler $handler): void;

    public function publish(InterfaceCommand $command);
}
