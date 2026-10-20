<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Password;

/**
 * Contraseñas elegidas por humanos: hash lento con sal (password_hash, bcrypt por defecto), al contrario que
 * los api_token (TokenGenerator), que son aleatorios y les basta un SHA-256.
 */
final class PasswordHasher
{
    /**
     * Hash de 32 bytes aleatorios ya descartados (nadie conoce la contraseña): se verifica contra el cuando el
     * usuario no existe o no tiene contraseña, para que la respuesta tarde lo mismo y no delate que user_name existen.
     */
    private const string DUMMY_HASH = '$2y$12$kUEK9r0sqApY2hnb2ZtJIe4JTyxNGppqF0iNfU7ThCu6i/.FMhrHi';

    public static function getInstance(): self
    {
        return new self();
    }

    public function getPasswordHash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @param string $passwordHash "" = sin contraseña guardada: compara contra el hash ficticio y devuelve false
     */
    public function isPasswordValid(string $password, string $passwordHash): bool
    {
        if (!$passwordHash) {
            password_verify($password, self::DUMMY_HASH);
            return false;
        }
        return password_verify($password, $passwordHash);
    }

    /**
     * true si el hash se hizo con un algoritmo o coste antiguo: se rehace en el siguiente login correcto.
     */
    public function doesPasswordHashNeedRehash(string $passwordHash): bool
    {
        return password_needs_rehash($passwordHash, PASSWORD_DEFAULT);
    }
}
