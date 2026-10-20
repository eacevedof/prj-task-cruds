<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\TplReader;

use RuntimeException;

/**
 * Plantilla .php renderizada con sus variables (de prj-mypromos, Components/TplReader).
 */
final class TplReader
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws RuntimeException
     */
    public function getFileContent(string $pathTpl, array $tplVars): string
    {
        if (!is_file($pathTpl)) {
            throw new RuntimeException("TplReader: file not found: {$pathTpl}");
        }

        ob_start();
        extract($tplVars);
        include $pathTpl;
        return (string) ob_get_clean();
    }
}
