<?php
declare(strict_types=1);

namespace App\Modules\Shared\Application;

use App\Modules\Shared\Domain\Enums\LanguageCodeEnum;

/**
 * Metadata de la peticion que viaja con TODO dto de entrada hasta la logica de negocio (ip, navegador, so,
 * zona horaria, idioma). La rellena el controller (HttpRequester::getRequestMetadata), que es quien lee el transporte: el
 * servicio solo ve su dto. Desde CLI o un test llega vacia y el idioma cae en EN.
 * El dto hijo llama primero a parent::__construct($primitives).
 */
abstract readonly class AbstractInputDto
{
    protected string $userIpAddress;
    protected string $userBrowser;
    protected string $userBrowserVersion;
    protected string $userOs;
    protected string $userTimeZone;
    protected string $userLanguageCode;

    public function __construct(array $primitives)
    {
        $this->userIpAddress = trim((string) ($primitives["userIpAddress"] ?? ""));
        $this->userBrowser = trim((string) ($primitives["userBrowser"] ?? ""));
        $this->userBrowserVersion = trim((string) ($primitives["userBrowserVersion"] ?? ""));
        $this->userOs = trim((string) ($primitives["userOs"] ?? ""));
        $this->userTimeZone = trim((string) ($primitives["userTimeZone"] ?? ""));
        $this->userLanguageCode = trim((string) ($primitives["userLanguageCode"] ?? "")) ?: LanguageCodeEnum::EN;
    }

    public function getUserIpAddress(): string
    {
        return $this->userIpAddress;
    }

    public function getUserBrowser(): string
    {
        return $this->userBrowser;
    }

    public function getUserBrowserVersion(): string
    {
        return $this->userBrowserVersion;
    }

    public function getUserOs(): string
    {
        return $this->userOs;
    }

    public function getUserTimeZone(): string
    {
        return $this->userTimeZone;
    }

    public function getUserLanguageCode(): string
    {
        return $this->userLanguageCode;
    }
}
