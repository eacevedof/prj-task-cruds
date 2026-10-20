<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Server;

/**
 * Nombre e ip de la maquina que ejecuta la app (de prj-mypromos, Components/Server/Server): en un contenedor,
 * su hostname y su ip en la red de docker.
 */
final class ServerIdentifier
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function getServerName(): string
    {
        return gethostname() ?: "";
    }

    public function getServerIp(): string
    {
        return gethostbyname($this->getServerName());
    }
}
