<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Bus\Event;

use App\Modules\Shared\Domain\Bus\Event\InterfaceEvent;

interface InterfaceEventSubscriber
{
    public function onSubscribedEvent(InterfaceEvent $domainEvent): self;
}
