<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Validators;

use App\Modules\Users\Domain\Enums\UsersMessageEnum;

/**
 * Reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por
 * cada regla incumplida; vacio = valido.
 */
final class UserFieldsValidator
{
    public const int USER_NAME_MAX_LENGTH = 100;
    public const int USER_EMAIL_MAX_LENGTH = 150;

    public static function getInstance(): self
    {
        return new self();
    }

    public function getErrors(string $userName, string $userEmail): array
    {
        $errors = [];
        if ($userName === "") {
            $errors[] = "user_name: ".__(UsersMessageEnum::FIELD_IS_REQUIRED);
        }
        elseif (mb_strlen($userName) > self::USER_NAME_MAX_LENGTH) {
            $errors[] = "user_name: ".__(UsersMessageEnum::MAX_LENGTH_IS_X, self::USER_NAME_MAX_LENGTH);
        }

        if ($userEmail === "") {
            $errors[] = "user_email: ".__(UsersMessageEnum::FIELD_IS_REQUIRED);
        }
        elseif (mb_strlen($userEmail) > self::USER_EMAIL_MAX_LENGTH) {
            $errors[] = "user_email: ".__(UsersMessageEnum::MAX_LENGTH_IS_X, self::USER_EMAIL_MAX_LENGTH);
        }
        elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "user_email: ".__(UsersMessageEnum::INVALID_EMAIL);
        }
        return $errors;
    }
}
