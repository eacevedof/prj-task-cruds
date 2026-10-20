<?php
declare(strict_types=1);

namespace App\Modules\Open\Documentation\Infrastructure\Views;

final readonly class DocumentationViewDto
{
    public function __construct(
        private string $appVersion,
        private string $appVersionUpdate,
        private string $appBaseUrl,
        private array $errors,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            trim((string) ($primitives["appVersion"] ?? "")),
            trim((string) ($primitives["appVersionUpdate"] ?? "")),
            trim((string) ($primitives["appBaseUrl"] ?? "")),
            $primitives["errors"] ?? [],
        );
    }

    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    public function getAppVersionUpdate(): string
    {
        return $this->appVersionUpdate;
    }

    public function getAppBaseUrl(): string
    {
        return $this->appBaseUrl;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
