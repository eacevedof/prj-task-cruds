<?php
declare(strict_types=1);

namespace App\Modules\Devops\Application\RunMigrations;

final readonly class RunMigrationsResultDto
{
    /**
     * Traza de lo ocurrido, en orden: la imprime el command (el servicio no escribe en stdout).
     */
    private array $messages;

    public function __construct(array $primitives)
    {
        $this->messages = array_map(
            fn(mixed $message): string => trim((string) $message),
            $primitives["messages"] ?? []
        );
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
