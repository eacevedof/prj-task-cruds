<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

/**
 * Un servicio no guarda errores: si falla, escala con su excepcion y ahi acaba. Este trait solo da formato a
 * una pila de errores (p.ej. la de un validador) para meterla en el mensaje de esa excepcion:
 * FieldsException::badRequestCustom($this->getErrorStackMessage(__(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors));
 */
trait ErrorStackTrait
{
    /**
     * @param string[] $errorStack
     * @return string "Fields validation errors: user_name: field is required; user_email: invalid email"
     */
    private function getErrorStackMessage(string $title, array $errorStack): string
    {
        return "{$title}: ".implode("; ", $errorStack);
    }
}
