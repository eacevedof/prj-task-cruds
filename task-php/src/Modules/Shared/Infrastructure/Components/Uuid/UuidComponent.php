<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Uuid;

/**
 * UUID v4 (RFC 4122) de los *_uuid publicos. Formato estandar para que todas las replicas
 * (Python, Go, Rust, JS...) generen y validen lo mismo.
 */
final class UuidComponent
{
    private const string UUID_V4_PATTERN = "/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/";

    public static function getInstance(): self
    {
        return new self();
    }

    public function getUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf(
            "%s-%s-%s-%s-%s",
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    public function isUuidV4(string $uuid): bool
    {
        return (bool) preg_match(self::UUID_V4_PATTERN, $uuid);
    }
}
