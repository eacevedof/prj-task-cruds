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
    private readonly DateTimer $dateTimer;
    private readonly FuncMailer $funcMailer;
    private readonly ServerIdentifier $serverIdentifier;
    private readonly EnvironmentReaderRawRepository $environmentReaderRawRepository;

    private SendAlertEmailDto $sendAlertEmailDto;

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
        $this->sendAlertEmailDto = $sendAlertEmailDto;
        $this->failIfWrongInput();

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
            ->setSubject($this->getSubject($environment))
            ->setContent($this->getHtmlBody())
            ->send();

        return SendAlertEmailResultDto::fromPrimitives(["isSent" => true, "emailTo" => $emailTo]);
    }

    /**
     * La alerta la genera el codigo (MailTrait), no un usuario: un dato mal formado es un fallo de programacion.
     * @throws RuntimeException
     */
    private function failIfWrongInput(): void
    {
        $alertType = $this->sendAlertEmailDto->getAlertType();
        if (!isset(AlertEmailTypeEnum::SUBJECT_TAGS[$alertType])) {
            throw new RuntimeException("SendAlertEmail: unknown alert type \"{$alertType}\"");
        }
        if (!$this->sendAlertEmailDto->getSubject()) {
            throw new RuntimeException("SendAlertEmail: subject is required");
        }
    }

    private function getSubject(string $environment): string
    {
        $subjectTag = AlertEmailTypeEnum::SUBJECT_TAGS[$this->sendAlertEmailDto->getAlertType()];
        $serverInfo = $this->serverIdentifier->getServerName()." (".$this->serverIdentifier->getServerIp().")";
        return "$subjectTag ($environment) $serverInfo - {$this->sendAlertEmailDto->getSubject()}";
    }

    private function getHtmlBody(): string
    {
        $alertType = $this->sendAlertEmailDto->getAlertType();
        $heading = AlertEmailTypeEnum::HEADINGS[$alertType];
        $color = AlertEmailTypeEnum::COLORS[$alertType];
        $now = $this->dateTimer->getNowDtIntoTargetTz();
        $baseUrl = htmlspecialchars($this->environmentReaderRawRepository->getBaseUrl());
        $subject = htmlspecialchars($this->sendAlertEmailDto->getSubject());
        $details = htmlspecialchars($this->sendAlertEmailDto->getDetails());

        return "
            <h3 style=\"color:$color;\">[$now] $heading: $subject</h3>
            <p><a href=\"$baseUrl\" target=\"_blank\">$baseUrl</a></p>
            <pre>$details</pre>
        ";
    }
}
