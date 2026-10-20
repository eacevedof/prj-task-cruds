<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Token;

/**
 * Tokens de API opacos: 32 bytes del CSPRNG en hex (256 bits). Con esa entropia basta un SHA-256 para
 * guardarlos (no hace falta un hash lento tipo bcrypt, que es para contraseñas elegidas por humanos).
 */
final class TokenGenerator
{
    private const int TOKEN_BYTES = 32;
    private const string HASH_ALGORITHM = "sha256";

    public static function getInstance(): self
    {
        return new self();
    }

    public function getNewToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_BYTES));
    }

    public function getTokenHash(string $token): string
    {
        return hash(self::HASH_ALGORITHM, $token);
    }
}
