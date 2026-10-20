<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Mailer2;

use RuntimeException;

use App\Modules\Shared\Infrastructure\Components\Mailer\FuncMailer;
use App\Modules\Shared\Infrastructure\Components\TplReader\TplReader;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;

/**
 * Builder fluido sobre FuncMailer (mail() nativo). Componente: no captura ni loguea, si no se puede enviar
 * lanza RuntimeException.
 * Config por env: APP_EMAIL_FROM, APP_EMAIL_FROM_NAME, APP_EMAIL_DEFAULT_TPL.
 */
final class Mailer2
{
    private string $emailFrom = "";
    private string $emailFromName = "";
    private string $emailTo = "";
    private string $subject = "";

    private string $tplView = "";
    private array $tplVars = [];

    private array $emailsCc = [];
    private array $attachments = [];

    private string $message = "";

    public static function getInstance(): self
    {
        return new self();
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function setEmailTo(string $emailTo): self
    {
        $this->emailTo = $emailTo;
        return $this;
    }

    public function setEmailFromName(string $emailFromName): self
    {
        $this->emailFromName = $emailFromName;
        return $this;
    }

    public function setEmailFrom(string $emailFrom): self
    {
        $this->emailFrom = $emailFrom;
        return $this;
    }

    public function addEmailCc(string $emailCc): self
    {
        $this->emailsCc[] = $emailCc;
        return $this;
    }

    /**
     * @param array{path: string, mime?: string, filename?: string} $attachment
     */
    public function addAttachment(array $attachment): self
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function setTplView(string $tplView, array $tplVars = []): self
    {
        $this->tplView = $tplView;
        $this->tplVars = $tplVars;
        return $this;
    }

    /**
     * @param array $tplVars ["subject" => "...", "body" => "..."]
     */
    public function setDefaultTplVars(array $tplVars): self
    {
        $this->tplView = EnvironmentReaderRawRepository::getInstance()->getEmailDefaultTpl();
        $this->tplVars = $tplVars;
        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @throws RuntimeException
     */
    public function sendSynchronousByTpl(): void
    {
        $this->failIfWrongInput();
        if (!$this->tplView) {
            throw new RuntimeException("Mailer2: tpl view is required");
        }

        $this->sendWithContent(
            TplReader::getInstance()->getFileContent($this->tplView, $this->tplVars)
        );
    }

    /**
     * @throws RuntimeException
     */
    public function sendSynchronousByMessage(): void
    {
        $this->failIfWrongInput();
        $this->sendWithContent($this->message);
    }

    public function reset(): self
    {
        $this->subject = "";
        $this->tplView = "";
        $this->tplVars = [];

        $this->emailTo = "";
        $this->emailFrom = "";
        $this->emailFromName = "";
        $this->emailsCc = [];
        $this->attachments = [];

        $this->message = "";

        return $this;
    }

    /**
     * @throws RuntimeException
     */
    private function sendWithContent(string $content): void
    {
        $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
        $emailFrom = $this->emailFrom ?: $environmentReaderRawRepository->getEmailFrom();
        $emailFromName = $this->emailFromName ?: ($environmentReaderRawRepository->getEmailFromName() ?: $emailFrom);

        $funcMailer = FuncMailer::getInstance()
            ->setFrom($emailFrom)
            ->setTitleFrom($emailFromName)
            ->addTo($this->emailTo)
            ->setSubject($this->subject)
            ->setContent($content);

        foreach (array_unique($this->emailsCc) as $emailCc) {
            $funcMailer->addCc($emailCc);
        }

        foreach ($this->attachments as $attachment) {
            if (!isset($attachment["path"])) continue;
            $funcMailer->addAttachment([
                "path" => $attachment["path"],
                "mime" => $attachment["mime"] ?? "application/octet-stream",
                "filename" => $attachment["filename"] ?? basename($attachment["path"]),
            ]);
        }

        $funcMailer->send();
    }

    /**
     * @throws RuntimeException
     */
    private function failIfWrongInput(): void
    {
        if (!$emailTo = $this->emailTo) {
            throw new RuntimeException("Mailer2: email-to is required");
        }
        if (filter_var($emailTo, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException("Mailer2: email-to {$emailTo} is not a valid email");
        }
        if (!$this->subject && !$this->tplVars) {
            throw new RuntimeException("Mailer2: subject or vars are required");
        }
    }
}
