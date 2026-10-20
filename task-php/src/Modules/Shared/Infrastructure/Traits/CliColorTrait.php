<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

/**
 * Colores ANSI para la consola (copia de app-plataformabase): getXxx() devuelve el texto coloreado para
 * componer una linea, echoXxx() la imprime entera por stdout. Sin dieRed: el codigo de salida lo pone
 * AbstractConsoleCommand (1 dominio, 2 infraestructura).
 * Los metodos son private: cada command que pinte hace su propio use CliColorTrait.
 */
trait CliColorTrait
{
    private function echoRed(string $text): void
    {
        echo $this->getRed($text).PHP_EOL;
    }

    private function getRed(string $text): string
    {
        return "\033[91m{$text}\033[0m";
    }

    private function echoGreen(string $text): void
    {
        echo $this->getGreen($text).PHP_EOL;
    }

    private function getGreen(string $text): string
    {
        return "\033[92m{$text}\033[0m";
    }

    private function echoYellow(string $text): void
    {
        echo $this->getYellow($text).PHP_EOL;
    }

    private function getYellow(string $text): string
    {
        return "\033[93m{$text}\033[0m";
    }

    private function echoBlue(string $text): void
    {
        echo $this->getBlue($text).PHP_EOL;
    }

    private function getBlue(string $text): string
    {
        return "\033[94m{$text}\033[0m";
    }

    private function echoWhite(string $text): void
    {
        echo $this->getWhite($text).PHP_EOL;
    }

    private function getWhite(string $text): string
    {
        return "\033[97m{$text}\033[0m";
    }

    private function echoOrange(string $text): void
    {
        echo $this->getOrange($text).PHP_EOL;
    }

    private function getOrange(string $text): string
    {
        return "\033[38;5;214m{$text}\033[0m";
    }
}
