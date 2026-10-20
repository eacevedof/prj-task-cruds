<?php
declare(strict_types=1);

namespace App\Modules\Mailer\Application\SendAlertEmail;

use RuntimeException;

use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Mailer\FuncMailer;
use App\Modules\Shared\Infrastructure\Components\Server\ServerIdentifier;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;

use App\Modules\Mailer\Domain\Enums\AlertEmailTypeEnum;

/**
 * Email de alerta al equipo (error, amenaza de seguridad, warning, notice) a APP_EMAIL_TO.
 * Sin APP_EMAIL_TO no envia (alertas desactivadas). No captura: si el envio falla escala, y quien lo usa
 * (MailTrait) decide que hacer para no tapar el error original.
 */
final class SendAlertEmailService
{
    private DateTimer $dateTimer;
    private FuncMailer $funcMailer;
    private ServerIdentifier $serverIdentifier;
    private EnvironmentReaderRawRepository $environmentReaderRawRepository;

    public function __construct()
    {
        $this->dateTimer = DateTimer::getInstance();
        $this->funcMailer = FuncMailer::getInstance();
        $this->serverIdentifier = ServerIdentifier::getInstance();
        $this->environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws RuntimeException
     */
    public function __invoke(SendAlertEmailDto $sendAlertEmailDto): SendAlertEmailResultDto
    {
        $emailTo = $this->environmentReaderRawRepository->getEmailTo();
        if (!$emailTo) {
            return SendAlertEmailResultDto::fromPrimitives(["isSent" => false, "emailTo" => ""]);
        }

        $environment = $this->environmentReaderRawRepository->getEnvironment();
        $appName = $this->environmentReaderRawRepository->getAppName();
        $this->funcMailer
            ->setFrom($this->environmentReaderRawRepository->getEmailFrom() ?: $emailTo)
            ->setTitleFrom("$appName ($environment)")
            ->addTo($emailTo)
            ->setSubject($this->getSubject($sendAlertEmailDto, $environment))
            ->setContent($this->getHtmlBody($sendAlertEmailDto))
            ->send();

        return SendAlertEmailResultDto::fromPrimitives(["isSent" => true, "emailTo" => $emailTo]);
    }

    private function getSubject(SendAlertEmailDto $sendAlertEmailDto, string $environment): string
    {
        $subjectTag = AlertEmailTypeEnum::SUBJECT_TAGS[$sendAlertEmailDto->getAlertType()] ?? "[alert]";
        $serverInfo = $this->serverIdentifier->getServerName()." (".$this->serverIdentifier->getServerIp().")";
        return "$subjectTag ($environment) $serverInfo - {$sendAlertEmailDto->getSubject()}";
    }

    private function getHtmlBody(SendAlertEmailDto $sendAlertEmailDto): string
    {
        $alertType = $sendAlertEmailDto->getAlertType();
        $heading = AlertEmailTypeEnum::HEADINGS[$alertType] ?? "Alert";
        $color = AlertEmailTypeEnum::COLORS[$alertType] ?? "#D60202";
        $now = $this->dateTimer->getNowDtIntoTargetTz();
        $baseUrl = htmlspecialchars($this->environmentReaderRawRepository->getBaseUrl());
        $subject = htmlspecialchars($sendAlertEmailDto->getSubject());
        $details = htmlspecialchars($sendAlertEmailDto->getDetails());

        return "
            <h3 style=\"color:$color;\">[$now] $heading: $subject</h3>
            <p><a href=\"$baseUrl\" target=\"_blank\">$baseUrl</a></p>
            <pre>$details</pre>
        ";
    }
}
