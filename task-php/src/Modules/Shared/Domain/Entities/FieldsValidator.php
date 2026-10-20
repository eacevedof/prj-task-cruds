<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Entities;

use App\Modules\Shared\Domain\Enums\EntityEnum;
use App\Modules\Shared\Domain\Entities\AppEntity;
use App\Modules\Shared\Infrastructure\Components\Formatter\TextComponent;

final class FieldsValidator
{
    private array $rules = [];
    private array $requestArray;
    private ?AppEntity $appEntity;
    private array $errors = [];
    private array $skippAbleFields = [];

    public function __construct(array | object $request, ?AppEntity $entity = null)
    {
        $this->requestArray = is_object($request)
            ? TextComponent::getInstance()->getObjectAsArrayInSnakeCase($request)
            : $request;
        $this->appEntity = $entity;
    }

    public static function getInstance(
        array | object $request,
        ?AppEntity $entity = null
    ): self
    {
        return new self($request, $entity);
    }

    public function getErrors(): array
    {
        $requestKeys = $this->getKeysFromArrayRequest();
        $this->checkEntityFields($requestKeys);
        if ($this->errors) {
            return $this->errors;
        }
        $this->evaluateAllRulesAndAppendErrors();
        return $this->errors;
    }

    public function addSkipableField(string $fieldName): self
    {
        $this->skippAbleFields[] = $fieldName;
        return $this;
    }

    public function addRule(string $fieldName, string $ruleName, callable $fn): self
    {
        $this->rules[] = ["field" => $fieldName, "rule" => $ruleName, "fn" => $fn];
        return $this;
    }

    public function getSkippAbleFields(): array
    {
        return $this->skippAbleFields;
    }

    private function isLengthOk(string $fieldName): bool
    {
        $requestKey = $this->appEntity->getRequestKeyByFieldName($fieldName);
        $fieldLength = $this->appEntity->getLength($fieldName);
        $value = $this->requestArray[$requestKey] ?? "";
        return (strlen((string) $value) <= $fieldLength);
    }

    private function isDatetimeOk(string $datetime): bool
    {
        $datetime = str_replace("T", " ", $datetime);
        return (date("Y-m-d H:i:s", (int) strtotime($datetime)) == $datetime);
    }

    private function isFieldTypeDate(string $fieldName): bool
    {
        return in_array(
            $this->appEntity->getTypeByFieldName($fieldName),
            [EntityEnum::DATE, EntityEnum::DATETIME]
        );
    }

    private function isFieldTypeOk(string $fieldName): bool
    {
        $requestKey = $this->appEntity->getRequestKeyByFieldName($fieldName);
        $requestValue = $this->requestArray[$requestKey] ?? null;
        if ($this->isEmpty($requestValue)) {
            return true;
        }

        $fieldType = $this->appEntity->getTypeByFieldName($fieldName);
        return match ($fieldType) {
            EntityEnum::INT => is_numeric($requestValue),
            EntityEnum::DECIMAL => is_float($requestValue) || is_numeric($requestValue),
            EntityEnum::DATE => (bool)strtotime((string)$requestValue),
            EntityEnum::DATETIME => $this->isDatetimeOk((string)$requestValue),
            EntityEnum::STRING => is_string($requestValue),
            default => false,
        };
    }

    private function isEmpty(int|float|string|null $val): bool
    {
        return $val === "" || is_null($val);
    }

    private function getKeysFromArrayRequest(): array
    {
        return array_keys($this->requestArray);
    }

    private function evaluateAllRulesAndAppendErrors(): void
    {
        foreach($this->rules as $rule) {
            $field = $rule["field"];
            $requestKey = $field;
            $label = $this->requestArray["label-$field"] ?? "";

            if ($this->appEntity) {
                $requestKey = $this->appEntity->getRequestKeyByFieldName($field);
                $label = $this->appEntity->getLabel($field);
            }

            $message = $rule["fn"]([
                "data" => $this->requestArray,
                "field" => $field,
                "value" => $this->requestArray[$requestKey] ?? null,
                "label" => $label
            ]);

            if ($message) {
                $this->addError($requestKey, $rule["rule"], $message, $label);
            }

        }//foreach
    }

    private function addError(string $fieldName, string $ruleName, string $message, string $label): void
    {
        $this->errors[] = [
            "field" => $fieldName,
            "rule" => $ruleName,
            "label" => $label,
            "message" => $message,
        ];
    }

    private function isOperationField(string $requestKey): bool
    {
        return (str_starts_with($requestKey, "_"));
    }

    private function isFieldSkippAble(string $requestKey): bool
    {
        return in_array($requestKey, $this->skippAbleFields);
    }

    private function checkEntityFields(array $requestKeys): void
    {
        if (!$this->appEntity) {
            return;
        }

        foreach ($requestKeys as $requestKey) {
            if ($this->isOperationField($requestKey) || $this->isFieldSkippAble($requestKey)) {
                continue;
            }

            $field = $this->appEntity->getFieldNamedByRequestKey($requestKey);
            if (!$field) {
                $this->addError(
                    $requestKey,
                    "unrecognized",
                    __("Unrecognized field"),
                    ""
                );
                continue;
            }

            $label = $this->appEntity->getLabel($field);
            if (!($this->isLengthOk($field) || $this->isFieldTypeDate($field))) {
                $fieldLength = $this->appEntity->getLength($field);
                $requestValueLength = strlen((string) ($this->requestArray[$requestKey] ?? ""));

                $this->addError(
                    $requestKey,
                    "length",
                    __("Max length allowed is {0} chars. {1} size is {2}", $fieldLength, $label, $requestValueLength),
                    $label
                );
                continue;
            }

            if (!$this->isFieldTypeOk($field)) {
                $type = $this->appEntity->getTypeByFieldName($field);
                $this->addError(
                    $requestKey,
                    "type",
                    __("Wrong datatype. Allowed: {0}", $type),
                    $label
                );
            }
        }
    }
}
