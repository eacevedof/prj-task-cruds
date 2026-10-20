<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

trait ErrorTrait
{
    protected array $errors = [];
    protected bool $isError = false;

    public function isError(): bool
    {
        return $this->isError;
    }
    public function getErrors(): array
    {
        return $this->errors;
    }
    public function getErrorByPosition(int $position = 0): mixed
    {
        return $this->errors[$position] ?? null;
    }

    protected function addError(string $message): void
    {
        $this->isError = true;
        $this->errors[] = $message;
    }

    protected function addErrors(array $messages): void
    {
        $this->isError = true;
        foreach ($messages as $message) {
            $this->errors[] = $message;
        }
    }

    protected function setErrors(array $errors): void
    {
        $this->isError = true;
        $this->errors = $errors;
    }

}//ErrorTrait
