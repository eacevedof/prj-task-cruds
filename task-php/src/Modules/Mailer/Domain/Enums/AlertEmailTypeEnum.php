<?php
declare(strict_types=1);

namespace App\Modules\Mailer\Domain\Enums;

/**
 * Tipos de email de alerta: cada uno con su etiqueta en el asunto, su cabecera y su color en el cuerpo.
 */
abstract class AlertEmailTypeEnum
{
    public const string ERROR = "error";
    public const string SECURITY_THREAT = "security_threat";
    public const string WARNING = "warning";
    public const string NOTICE = "notice";

    public const array SUBJECT_TAGS = [
        self::ERROR => "[alert]",
        self::SECURITY_THREAT => "[alert]: Security Threat",
        self::WARNING => "[warning]",
        self::NOTICE => "[notice]",
    ];

    public const array HEADINGS = [
        self::ERROR => "Error",
        self::SECURITY_THREAT => "Security Threat Alert",
        self::WARNING => "Warning",
        self::NOTICE => "Notice",
    ];

    public const array COLORS = [
        self::ERROR => "#D60202",
        self::SECURITY_THREAT => "#D60202",
        self::WARNING => "darkorange",
        self::NOTICE => "royalblue",
    ];
}
