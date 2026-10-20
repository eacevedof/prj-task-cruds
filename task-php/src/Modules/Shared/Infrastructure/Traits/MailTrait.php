<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

use Throwable;

use App\Modules\Mailer\Domain\Enums\AlertEmailTypeEnum;
use App\Modules\Mailer\Application\SendAlertEmail\SendAlertEmailDto;
use App\Modules\Mailer\Application\SendAlertEmail\SendAlertEmailService;

/**
 * Emails de alerta desde un controller o un command (de app-plataformabase / prj-mypromos, MailTrait).
 * Se llama desde su catch: por eso aqui SI se captura, un fallo al enviar se loguea y nunca tapa el error
 * original. Quien lo use necesita tambien LogTrait (logException).
 */
trait MailTrait
{
    protected function sendMailOnThrowable(Throwable $e, string $title): void
    {
        $this->sendAlertEmail(AlertEmailTypeEnum::ERROR, $title, implode(PHP_EOL, [
            $e::class.": ".$e->getMessage(),
            "File: {$e->getFile()} (line {$e->getLine()})",
            "",
            "Trace:",
            $e->getTraceAsString(),
        ]));
    }

    protected function sendEmailOnSecurityThreat(string $message, string $subject = ""): void
    {
        $this->sendAlertEmail(AlertEmailTypeEnum::SECURITY_THREAT, $subject, $message);
    }

    protected function sendEmailWarning(string $message, string $subject = ""): void
    {
        $this->sendAlertEmail(AlertEmailTypeEnum::WARNING, $subject, $message);
    }

    protected function sendEmailNotice(string $message, string $subject): void
    {
        $this->sendAlertEmail(AlertEmailTypeEnum::NOTICE, $subject, $message);
    }

    private function sendAlertEmail(string $alertType, string $subject, string $details): void
    {
        try {
            SendAlertEmailService::getInstance()->__invoke(
                SendAlertEmailDto::fromPrimitives([
                    "alertType" => $alertType,
                    "subject" => $subject,
                    "details" => $details,
                ])
            );
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
        }
    }
}
