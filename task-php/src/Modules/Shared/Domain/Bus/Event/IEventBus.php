<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Bus\Event;

use App\Modules\Shared\Domain\Bus\Event\IEvent;

interface IEventBus
{
    public function publish(IEvent ...$events): void;
}
