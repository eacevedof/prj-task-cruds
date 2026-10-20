<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Bus;

use InvalidArgumentException;

use App\Modules\Shared\Domain\Bus\Command\InterfaceCommandBus;
use App\Modules\Shared\Domain\Bus\Command\InterfaceCommandHandler;
use App\Modules\Shared\Domain\Bus\Command\InterfaceCommand;

final class CommandBus implements InterfaceCommandBus
{
    private array $handlers;
    private static ?CommandBus $instance = null;

    private function __construct()
    {
        $this->handlers = [];
    }

    public static function getInstance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function subscribe(string $command, InterfaceCommandHandler $handler): void
    {
        $this->handlers[$command] = $handler;
    }

    public function publish(InterfaceCommand $command)
    {
        $commandHandler = $this->handlers[get_class($command)] ?? "";
        if (!$commandHandler) {
            throw new InvalidArgumentException;
        }
        //usa el invoke
        return $commandHandler($command);
    }

    public function __clone()
    {
        throw new \BadMethodCallException("Clone is not supported");
    }

    public function unsubscribe(string $command): self
    {
        unset($this->handlers[$command]);
        return $this;
    }
}
