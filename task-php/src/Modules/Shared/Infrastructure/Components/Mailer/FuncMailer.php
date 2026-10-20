<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Mailer;

use RuntimeException;

/**
 * Envia con mail() de PHP (de prj-mypromos, Components/Email/FuncEmailComponent): html y adjuntos opcionales.
 * mail() entrega a sendmail_path; en local es el sendmail de busybox apuntando a Mailpit (docker/fpm/Dockerfile).
 * Componente: no captura ni loguea, si no se puede enviar lanza RuntimeException.
 */
final class FuncMailer extends AbstractMailer
{
    private string $boundary = "";

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws RuntimeException
     */
    public function send(): void
    {
        if (!$this->emailFrom || !$this->emailsTo) {
            throw new RuntimeException("FuncMailer: email from and at least one email to are required");
        }

        $this->loadBoundary();
        $this->loadHeaderFrom();
        $this->loadHeaderMime();
        $this->loadHeaderCc();
        $this->loadHeaderBcc();

        $content = $this->getMultipart().PHP_EOL;
        $content .= $this->content.PHP_EOL;
        foreach ($this->attachments as $attachment) {
            $content .= $this->getAttachment($attachment);
        }

        // @: el warning de mail() se colaria en la respuesta (display_errors); su texto va en la excepcion
        $isSent = @mail(implode(", ", $this->emailsTo), $this->subject, $content, implode(PHP_EOL, $this->headers));
        if (!$isSent) {
            throw new RuntimeException("FuncMailer: mail() failed. ".(error_get_last()["message"] ?? ""));
        }
    }

    private function loadBoundary(): void
    {
        if ($this->attachments) {
            $this->boundary = md5(uniqid());
        }
    }

    private function loadHeaderFrom(): void
    {
        // nombre entre comillas: sin ellas, lo que va entre parentesis ("app (local)") es un comentario y se pierde
        $titleFrom = str_replace("\"", "", $this->titleFrom);
        $this->headers[] = "From: \"{$titleFrom}\" <{$this->emailFrom}>";
        $this->headers[] = "Return-Path: <{$this->emailFrom}>";
        $this->headers[] = "X-Sender: {$this->emailFrom}";
    }

    private function loadHeaderMime(): void
    {
        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=\"UTF-8\"",
            "Content-Transfer-Encoding: 8bit",
        ];

        if ($this->boundary) {
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"",
                "Content-Transfer-Encoding: 7bit",
                "This is a MIME encoded message."
            ];
        }
        $this->headers = array_merge($this->headers, $headers);
    }

    private function loadHeaderCc(): void
    {
        if ($this->emailsCc) {
            $this->headers[] = "cc: ".implode(", ", $this->emailsCc);
        }
    }

    private function loadHeaderBcc(): void
    {
        if ($this->emailsBcc) {
            $this->headers[] = "bcc: ".implode(", ", $this->emailsBcc);
        }
    }

    private function getMultipart(): string
    {
        if (!$this->boundary) {
            return "";
        }
        return implode(PHP_EOL, [
            "--{$this->boundary}",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
        ]);
    }

    /**
     * https://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
     */
    private function getAttachment(array $attachment): string
    {
        $pathFile = $attachment["path"] ?? "";
        if (!is_file($pathFile)) {
            return "";
        }

        $content = file_get_contents($pathFile);
        if (!$content) {
            return "";
        }

        $mime = $attachment["mime"] ?? "application/octet-stream";
        $alias = $attachment["filename"] ?? basename($pathFile);

        return implode(PHP_EOL, [
            "",
            "--{$this->boundary}",
            "Content-Type: $mime; name=\"$alias\"",
            "Content-Transfer-Encoding: base64",
            "Content-Disposition: attachment; ",
            chunk_split(base64_encode($content)),
            "--{$this->boundary}--",
            "",
        ]);
    }
}
