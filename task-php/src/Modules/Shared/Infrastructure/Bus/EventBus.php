<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Bus;

use App\Modules\Shared\Domain\Bus\Event\IEvent;
use App\Modules\Shared\Domain\Bus\Event\IEventBus;
use App\Modules\Shared\Domain\Bus\Event\IEventSubscriber;

final class EventBus implements IEventBus
{
    private array $subscribers;
    private static ?IEventBus $instance = null;
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

    public function subscribe(IEventSubscriber $subscriber): int
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
    public function publish(IEvent ...$domainEvents): void
    {
        foreach ($domainEvents as $event) {
            /**
             * @type IEventSubscriber $subscriberEventHandler
             */
            foreach($this->subscribers as $subscriberEventHandler) {
                $subscriberEventHandler->onSubscribedEvent($event);
            }
        }
    }

    public function ofId(int $id): ?IEventSubscriber
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
