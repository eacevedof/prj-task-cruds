<?php
declare(strict_types=1);

use App\Modules\Shared\Infrastructure\Bus\EventBus;

// Registro de los EventSubscribers de los modulos. Cada modulo nuevo anade aqui su subscribe().
$eventBus = EventBus::getInstance();
