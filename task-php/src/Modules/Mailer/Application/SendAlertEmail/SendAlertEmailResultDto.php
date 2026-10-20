<?php
declare(strict_types=1);

namespace App\Modules\Mailer\Application\SendAlertEmail;

final readonly class SendAlertEmailResultDto
{
    /**
     * false sin error: APP_EMAIL_TO vacio = alertas por email desactivadas (p.ej. en los tests)
     */
    private bool $isSent;
    private string $emailTo;

    public function __construct(array $primitives)
    {
        $this->isSent = (bool) ($primitives["isSent"] ?? false);
        $this->emailTo = trim((string) ($primitives["emailTo"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function isSent(): bool
    {
        return $this->isSent;
    }

    public function getEmailTo(): string
    {
        return $this->emailTo;
    }
}
