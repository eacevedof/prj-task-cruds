<?php
declare(strict_types=1);

namespace App\Modules\Mailer\Application\SendAlertEmail;

final readonly class SendAlertEmailDto
{
    private string $alertType;
    private string $subject;
    private string $details;

    /**
     * @param array $primitives alertType (AlertEmailTypeEnum), subject, details (texto plano: el servicio lo escapa)
     */
    public function __construct(array $primitives)
    {
        $this->alertType = trim((string) ($primitives["alertType"] ?? ""));
        $this->subject = trim((string) ($primitives["subject"] ?? ""));
        $this->details = trim((string) ($primitives["details"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getAlertType(): string
    {
        return $this->alertType;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getDetails(): string
    {
        return $this->details;
    }
}
