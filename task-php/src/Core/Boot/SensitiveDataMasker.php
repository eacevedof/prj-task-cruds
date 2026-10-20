<?php

declare(strict_types=1);

namespace App\Core\Boot;

final class SensitiveDataMasker
{
    private const string MASK = "***";

    private const array SENSITIVE_KEY_PARTS = [
        "password",
        "secret",
        "csrf",
        "token",
        "antibot",
    ];

    public static function getInstance(): self
    {
        return new self();
    }

    public function getMaskedArray(array $data): array
    {
        $masked = [];
        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $masked[$key] = self::MASK;
                continue;
            }
            $masked[$key] = is_array($value) ? $this->getMaskedArray($value) : $value;
        }
        return $masked;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        foreach (self::SENSITIVE_KEY_PARTS as $sensitiveKeyPart) {
            if (str_contains($key, $sensitiveKeyPart)) return true;
        }
        return false;
    }
}
