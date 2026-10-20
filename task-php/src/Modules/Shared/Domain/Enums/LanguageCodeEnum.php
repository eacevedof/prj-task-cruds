<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

/**
 * Idiomas que entiende la API (subetiqueta primaria de Accept-Language, ISO 639-1).
 */
abstract class LanguageCodeEnum
{
    public const string ES = "es";
    public const string EN = "en";

    public const array VALUES = [
        self::ES,
        self::EN,
    ];
}
