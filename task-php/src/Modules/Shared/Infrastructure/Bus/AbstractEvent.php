<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Bus;

use DateTimeImmutable;

use App\Modules\Shared\Domain\Bus\Event\InterfaceEvent;

//https://github.com/CodelyTV/php-ddd-example/blob/main/src/Shared/Domain/Bus/Event/DomainEvent.php
abstract class AbstractEvent implements InterfaceEvent
{
    private int $aggregateId;
    private string $eventId;
    private int $occurredOn;

    //id del evento inicial
    private string $correlationId;
    //id del padre
    private string $causationId;

    public function __construct(
        int     $aggregateId,
        ?string $eventId = null,
        ?int    $occurredOn = null,
        ?string $correlationId = null,
        ?string $causationId = null
    ) {
        $this->aggregateId = $aggregateId;
        $this->eventId = $eventId ?? uniqid();
        $this->occurredOn = $occurredOn ?? (new DateTimeImmutable)->getTimestamp();
        //creador original
        $this->correlationId = $correlationId ?? $this->eventId;
        //padre directo
        $this->causationId = $causationId ?? $this->correlationId;
    }

    abstract public static function fromPrimitives(
        int    $aggregateId,
        array  $body,
        string $eventId,
        int    $occurredOn,
        string $correlationId,
        string $causationId
    ): self;

    abstract public static function eventName(): string;

    abstract public function toPrimitives(): array;

    public function aggregateId(): int
    {
        return $this->aggregateId;
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function occurredOn(): int
    {
        return $this->occurredOn;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function causationId(): string
    {
        return $this->causationId;
    }
}
