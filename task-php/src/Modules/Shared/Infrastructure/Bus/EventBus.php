<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Bus;

use App\Modules\Shared\Domain\Bus\Event\InterfaceEvent;
use App\Modules\Shared\Domain\Bus\Event\InterfaceEventBus;
use App\Modules\Shared\Domain\Bus\Event\InterfaceEventSubscriber;

final class EventBus implements InterfaceEventBus
{
    private array $subscribers;
    private static ?InterfaceEventBus $instance = null;
    private int $id = 0;

    private function __construct()
    {
        $this->subscribers = [];
    }

    public static function getInstance(): self
    {
        if (self::$instance) return self::$instance;
        self::$instance = new self;
        return self::$instance;
    }

    public function subscribe(InterfaceEventSubscriber $subscriber): int
    {
        $key = $this->id;
        $this->subscribers[$key] = $subscriber;
        $this->id++;
        return $key;
    }

    /**
     * @info dispara la logica del event-handler a la escucha suscrito en:
     * backend_web/boot/listeners/event_bus.php
     */
    public function publish(InterfaceEvent ...$domainEvents): void
    {
        foreach ($domainEvents as $event) {
            /**
             * @type InterfaceEventSubscriber $subscriberEventHandler
             */
            foreach($this->subscribers as $subscriberEventHandler) {
                $subscriberEventHandler->onSubscribedEvent($event);
            }
        }
    }

    public function ofId(int $id): ?InterfaceEventSubscriber
    {
        return $this->subscribers[$id] ?? null;
    }

    public function unsubscribe(int $id): self
    {
        unset($this->subscribers[$id]);
        return $this;
    }

    private function __clone() {}

}
