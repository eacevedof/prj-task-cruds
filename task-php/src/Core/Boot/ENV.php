<?php
declare(strict_types=1);

namespace App\Core\Boot;

abstract class ENV
{
    public const string LOCAL = "local";
    public const string DEVELOP = "develop";
    public const string TEST = "test";
    public const string PRODUCTION = "production";

    public const array COLORS = [
        self::LOCAL => "#fc0ac3", //rosa
        self::DEVELOP => "#0174ee", //azul
        self::TEST => "#2f8a01", //verde
        self::PRODUCTION => "",
    ];

    // Catalogo de constantes de entorno. El COMPORTAMIENTO (getEnvironment/isLocal/isProduction/isDebug/color)
    // vive en App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository
    // (lee $_ENV, patron getInstance). Los antiguos estaticos basados en env se eliminaron por muertos (2026-08-11).
}
