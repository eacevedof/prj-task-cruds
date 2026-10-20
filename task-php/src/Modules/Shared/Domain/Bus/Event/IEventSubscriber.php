<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Bus\Event;

use App\Modules\Shared\Domain\Bus\Event\IEvent;

interface IEventSubscriber
{
    public function onSubscribedEvent(IEvent $domainEvent): self;
}
