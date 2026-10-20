<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Mailer;

/**
 * Datos comunes de un email (de prj-mypromos, Components/Email/AbstractEmail). El envio lo hace cada mailer.
 * Sin pila de errores: si no se puede enviar, send() lanza la excepcion.
 */
abstract class AbstractMailer implements InterfaceMailer
{
    protected array $headers = [];
    protected string $emailFrom = "";
    protected string $titleFrom = "";
    protected array $emailsTo = [];
    protected array $emailsCc = [];
    protected array $emailsBcc = [];
    protected array $attachments = [];

    protected string $subject = "";
    protected string $content = "";

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function setFrom(string $email): static
    {
        $this->emailFrom = $email;
        return $this;
    }

    public function setTitleFrom(string $title): static
    {
        $this->titleFrom = $title;
        return $this;
    }

    public function addTo(string $email): static
    {
        $this->emailsTo[] = $email;
        return $this;
    }

    public function addCc(string $email): static
    {
        $this->emailsCc[] = $email;
        return $this;
    }

    public function addBcc(string $email): static
    {
        $this->emailsBcc[] = $email;
        return $this;
    }

    /**
     * @param string|string[] $content html; un array se une con saltos de linea
     */
    public function setContent(string|array $content): static
    {
        $this->content = is_array($content) ? implode(PHP_EOL, $content) : $content;
        return $this;
    }

    /**
     * @param array{path: string, mime?: string, filename?: string} $attachment
     */
    public function addAttachment(array $attachment): static
    {
        $this->attachments[] = $attachment;
        return $this;
    }
}
