<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Services;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Enums\UsersMessageEnum;

/**
 * Reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por
 * cada regla incumplida; vacio = valido.
 */
final class UserFieldsValidator
{
    // user_name es el identificador de login: sin espacios, unico sin distinguir mayusculas (indice en BD)
    public const int USER_NAME_MIN_LENGTH = 3;
    public const int USER_NAME_MAX_LENGTH = 50;
    private const string USER_NAME_PATTERN = "/^[A-Za-z0-9._-]+$/";

    public const int USER_EMAIL_MAX_LENGTH = 150;

    // bcrypt solo usa los primeros 72 bytes: mas largo daria por buena una password que no coincide entera
    public const int PASSWORD_MIN_LENGTH = 8;
    public const int PASSWORD_MAX_BYTES = 72;

    public static function getInstance(): self
    {
        return new self();
    }

    public function getErrors(string $userName, string $userEmail): array
    {
        $errors = [];
        if ($userName === "") {
            $errors[] = UsersKeyEnum::USER_NAME.": ".__(UsersMessageEnum::FIELD_IS_REQUIRED);
        }
        elseif (mb_strlen($userName) < self::USER_NAME_MIN_LENGTH) {
            $errors[] = UsersKeyEnum::USER_NAME.": ".__(UsersMessageEnum::MIN_LENGTH_IS_X, self::USER_NAME_MIN_LENGTH);
        }
        elseif (mb_strlen($userName) > self::USER_NAME_MAX_LENGTH) {
            $errors[] = UsersKeyEnum::USER_NAME.": ".__(UsersMessageEnum::MAX_LENGTH_IS_X, self::USER_NAME_MAX_LENGTH);
        }
        elseif (!preg_match(self::USER_NAME_PATTERN, $userName)) {
            $errors[] = UsersKeyEnum::USER_NAME.": ".__(UsersMessageEnum::ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN);
        }

        if ($userEmail === "") {
            $errors[] = UsersKeyEnum::USER_EMAIL.": ".__(UsersMessageEnum::FIELD_IS_REQUIRED);
        }
        elseif (mb_strlen($userEmail) > self::USER_EMAIL_MAX_LENGTH) {
            $errors[] = UsersKeyEnum::USER_EMAIL.": ".__(UsersMessageEnum::MAX_LENGTH_IS_X, self::USER_EMAIL_MAX_LENGTH);
        }
        elseif (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = UsersKeyEnum::USER_EMAIL.": ".__(UsersMessageEnum::INVALID_EMAIL);
        }
        return $errors;
    }

    /**
     * La password llega con trim (como todo string de un dto) tanto en el alta como en el login: coinciden.
     */
    public function getPasswordErrors(string $password): array
    {
        if ($password === "") {
            return [UsersKeyEnum::PASSWORD.": ".__(UsersMessageEnum::FIELD_IS_REQUIRED)];
        }
        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            return [UsersKeyEnum::PASSWORD.": ".__(UsersMessageEnum::MIN_LENGTH_IS_X, self::PASSWORD_MIN_LENGTH)];
        }
        if (strlen($password) > self::PASSWORD_MAX_BYTES) {
            return [UsersKeyEnum::PASSWORD.": ".__(UsersMessageEnum::MAX_LENGTH_IS_X, self::PASSWORD_MAX_BYTES)];
        }
        return [];
    }
}
