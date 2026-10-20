<?php

namespace TheFramework\Components\Db\Context;

use Exception;

final class ComponentContext
{
    private $isError;
    private $errors;

    private $contexts;
    private $contextPublic;

    private $idSelected;
    private $selected;

    public function __construct(string $pathfile = "", string $idSelected = "")
    {
        $this->idSelected = $idSelected;
        $this->contexts = [];

        if (!$pathfile) {
            $pathfile = $_ENV["APP_CONTEXTS"] ?? __DIR__.DIRECTORY_SEPARATOR."contexts.json";
        }

        if (!is_file($pathfile)) {
            $this->addError("No context file found: $pathfile");
            return;
        }
        $this->loadArrayFromJson($pathfile);
        $this->loadPublicContexts();
        $this->loadSelectedContext();
    }

    private function loadArrayFromJson(string $pathfile): void
    {
        if ($pathfile) {
            if (is_file($pathfile)) {
                $json = file_get_contents($pathfile);
                $this->contexts = json_decode($json, 1, JSON_UNESCAPED_UNICODE);
                if (is_null($this->contexts)) {
                    throw new Exception("Contexts not loaded");
                }
            } else {
                $this->addError("_load_array_fromjson: file $pathfile not found");
            }
        } else {
            $this->addError("_load_array_fromjson: no pathfile passed");
        }
    }

    /**
     * carga la información que no es sensible, por eso se elimina schemas
     */
    private function loadPublicContexts(): void
    {
        foreach($this->contexts as $context) {
            unset($context["schemas"],$context["server"],$context["port"]);
            $this->contextPublic[] = $context;
        }
    }

    private function loadSelectedContext(): void
    {
        //pr($this->idSelected);
        //si no se pasa id se asume que no se ha seleccionado un contexto
        $this->selected["ctx"] = $this->getContextById($this->idSelected);
        //pr($this->arSelected,"arselected");die;
        if ($this->selected["ctx"]) {
            $this->selected["ctx"] = $this->selected["ctx"][array_keys($this->selected["ctx"])[0]];
        }

        $this->selected["pubconfig"] = $this->getPublicConfigByKV("id", $this->idSelected);
        //pr($this->arSelected,"arSelected");
    }

    private function getContextInLevel1By(
        string $key,
        string $value,
        array $array = []
    ): array {
        if (!$key && !$value) {
            return [];
        }
        if (!$array) {
            $array = $this->contexts;
        }

        $filtered = array_filter($array, function ($config) use ($key, $value) {
            $confval = $config[$key] ?? "";
            return $confval === $value;
        });
        return $filtered;
    }

    public function getContexts(): array
    {
        return $this->contexts;
    }

    public function getContextById(string $id): array
    {
        return $this->getContextInLevel1By("id", $id);
    }

    public function getContextByKV(string $key, string $val): array
    {
        return $this->getContextInLevel1By($key, $val);
    }

    public function getSchemasByKV(string $key, string $val): array
    {
        $config = $this->getContextInLevel1By($key, $val);
        if ($config) {
            $config = $config[array_keys($config)[0]];
            return $config["schemas"];
        }
        return [];
    }

    public function getSelected(): array
    {
        return $this->selected;
    }
    public function getSelectedId(): string
    {
        return $this->selected["ctx"]["id"] ?? "";
    }

    public function getSelectedSchemas(): array
    {
        return $this->selected["ctx"]["schemas"] ?? [];
    }

    public function getPublicConfigByKV(string $key, string $val): array
    {
        $config = $this->getContextInLevel1By($key, $val, $this->contextPublic);
        if ($config) {
            return $config[array_keys($config)[0]];
        }
        return [];
    }

    public function getPublicConfig(): array
    {
        return $this->contextPublic;
    }
    public function getErrors(): array
    {
        return isset($this->errors) ? $this->errors : [];
    }
    public function isError(): bool
    {
        return $this->isError;
    }
    public function getDbNameByAlias(string $alias): string
    {
        $schemas = $this->getSelectedSchemas();
        foreach ($schemas as $schema) {
            $schalias = $schema["alias"] ?? "";
            if ($schalias === $alias) {
                return $schema["database"] ?? "";
            }
        }
        return "";
    }

    private function addError(string $message): void
    {
        $this->isError = true;
        $this->errors[] = $message;
    }

}//ComponentContext

/*
Array
(
    [0] => Array
        (
            [id] => c1
            [alias] => Docker mysql
            [description] => Docker mysql
            [type] => mysql
            [server] => 127.0.0.1
            [port] => 3350
            [schemas] => Array
                (
                    [0] => Array
                        (
                            [database] => db_one
                            [user] => root
                            [password] => root
                        )

                    [1] => Array
                        (
                            [database] => db_two
                            [user] => root
                            [password] => root
                        )

                )

        )

    [1] => Array
        (
        )

)
*/
