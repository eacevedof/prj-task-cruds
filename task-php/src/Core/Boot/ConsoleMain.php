<?php

namespace App\Core\Boot;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;

final class ConsoleMain
{
    private static ?ConsoleMain $consoleMain = null;
    private array $argv;

    //?array a proposito: el $argv lo inyecta quien instancie (console.php pasa el global, pero
    //nada impide que otro caller mande null), asi que el ?? de abajo si protege.
    public function __construct(?array $argv = [])
    {
        if (!(($sapi = php_sapi_name()) === "cli")) {
            throw new Exception("sapi name not cli. {$sapi}");
        }
        $this->argv = $argv ?? [];
    }

    /**
     * @throws Exception
     */
    public static function getInstance(?array $argv = []): self
    {
        return new self($argv);
    }

    public static function onError(Throwable $ex): void
    {
        $uuid = uniqid();
        $sensitiveDataMasker = SensitiveDataMasker::getInstance();
        if ($_POST) {
            lgerr($sensitiveDataMasker->getMaskedArray($_POST), "console-exception $uuid POST", "error");
        }
        if ($_GET) {
            lgerr($sensitiveDataMasker->getMaskedArray($_GET), "console-exception $uuid GET", "error");
        }
        if ($_REQUEST) {
            lgerr($sensitiveDataMasker->getMaskedArray($_REQUEST), "console-exception $uuid REQUEST", "error");
        }
        lgerr($ex->getMessage(), "console-exception $uuid", "error");
        lgerr($ex->getTraceAsString(), "index-exception $uuid TRACE", "error");
        lgerr($ex->getFile()." : (line: {$ex->getLine()})", "file-line $uuid", "error");
    }

    public static function debug(Throwable $ex): void
    {
        $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
        if ($environmentReaderRawRepository->isProduction()) return;

        if (!$environmentReaderRawRepository->isDebug()) return;

        $content = [];
        $content["Exception"] = $ex->getMessage();
        $content["File"] = $ex->getFile()."(".$ex->getLine().")";
        $code = $ex->getCode() !== 0 ? $ex->getCode() : 500;
        $content["response"] = $code;

        if ($_ENV) {
            $content["App\Core\Boot\ENV"] = var_export($_ENV, true);
        }
        if ($_REQUEST) {
            $content["REQUEST"] = var_export($_REQUEST, true);
        }

        print_r($content);
    }

    public function exec(): void
    {
        $args = $this->getConsoleArgs($this->argv);
        if (isset($args["class"])) {
            $this->invokeClassWithMethodPassingArgv($args);
            return;
        }
        $this->invokeConsoleServiceWithArgs();
    }

    private function getConsoleArgs($argv): array
    {
        $_ARG = [];
        foreach ($argv as $arg_i) {
            if (preg_match("/--([^=]+)=(.*)/", $arg_i, $arKeyVal)) {
                $_ARG[$arKeyVal[1]] = $arKeyVal[2];
            }
            elseif (preg_match("/-([a-zA-Z0-9])/", $arg_i, $arKeyVal)) {
                $_ARG[$arKeyVal[1]] = "true";
            }
        }
        return $_ARG;
    }

    private function invokeClassWithMethodPassingArgv(array $argv): void
    {
        $className = $argv["class"];
        $className = str_replace(".", "\\", $className);
        $instance = new $className();

        if (!$argv["method"]) $argv["method"] = "run";

        $methodName = $argv["method"];
        $reflectionMethodObject = new ReflectionMethod($className, $methodName);

        $methodParams = [];
        foreach($reflectionMethodObject->getParameters() as $param) {
            //era $args: resto de un rename. El parametro se llama $argv, asi que $args no existia
            //y NINGUN comando de consola podia recibir argumentos: siempre caia al default y, si
            //el parametro no tenia default, reventaba con ReflectionException.
            if(isset($argv[$param->getName()])) {
                $methodParams[] =  $argv[$param->getName()];
            } else {
                $methodParams[] =  $param->getDefaultValue();
            }
        }
        $reflectionMethodObject->invokeArgs($instance, $methodParams);
    }

    private function invokeConsoleServiceWithArgs(): void
    {
        $alias = trim($this->argv[1] ?? "");
        if (!$alias) {
            $alias = "help";
        }

        $commands = include_once(BootPathsEnum::PATH_CONSOLE."/commands.php");
        $className = $commands[$alias]["service"] ?? "";
        if (!$className) {
            throw new Exception("no class found for cmd $alias");
        }

        $reflection = new ReflectionClass($className);
        //a partir de la pos 1 en adelante son parametros de input
        $input = array_slice($this->argv, 2);
        $object = $reflection->newInstanceArgs([$input]);
        if ($object) {
            $object->run();
        }
    }

}
