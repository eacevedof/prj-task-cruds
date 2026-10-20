<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Commands;

use Exception;
use Throwable;

use App\Console\Application\InterfaceConsole;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Traits\CliColorTrait;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;
use App\Modules\Shared\Infrastructure\Traits\MailTrait;

/**
 * El AbstractApiController de la consola (vault: CLI Commands as Controllers): lee --clave=valor, imprime el
 * ResultDto en JSON por stdout (sin color: se puede pasar a jq) y los errores en rojo por stderr con su codigo de salida.
 * 0 ok · 1 error de dominio (lo que en HTTP es un 4xx) · 2 error de infraestructura (el 500, se loguea).
 */
abstract class AbstractConsoleCommand implements InterfaceConsole
{
    use CliColorTrait, LogTrait, MailTrait;

    private const int EXIT_CODE_DOMAIN_ERROR = 1;
    private const int EXIT_CODE_INFRASTRUCTURE_ERROR = 2;

    /**
     * @param string[] $input los argumentos que van tras el alias del comando
     */
    public function __construct(
        protected readonly array $input
    ) {
    }

    /**
     * --user_name="Ana Lopez" → "Ana Lopez". Si no se pasa, "" (el servicio decide si es obligatorio).
     */
    protected function getOptionValue(string $optionName): string
    {
        $optionPrefix = "--{$optionName}=";
        foreach ($this->input as $argument) {
            if (str_starts_with((string) $argument, $optionPrefix)) {
                return substr((string) $argument, strlen($optionPrefix));
            }
        }
        return "";
    }

    protected function printResult(array $result): void
    {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
    }

    protected function exitWithDomainError(Exception $ex): never
    {
        $this->printErrors($ex->getCode(), [$ex->getMessage()]);
        exit(self::EXIT_CODE_DOMAIN_ERROR);
    }

    /**
     * Como el 500 de la API: se loguea con el metodo que fallo (__METHOD__) y se avisa por email. A diferencia de
     * la API, quien tiene la consola si ve el mensaje real (logDebugCli), como en los commands de app-anubis.
     */
    protected function exitWithUnexpectedError(Throwable $ex, string $logTitle): never
    {
        $this->logException($ex, $logTitle);
        $this->logDebugCli($ex->getMessage(), $logTitle);
        $this->sendMailOnThrowable($ex, $logTitle);
        $this->printErrors(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)]
        );
        exit(self::EXIT_CODE_INFRASTRUCTURE_ERROR);
    }

    /**
     * @param int $code el mismo que daria la API (404, 409, 400...), para que consola y HTTP se lean igual
     * @param string[] $errors
     */
    private function printErrors(int $code, array $errors): void
    {
        fwrite(STDERR, $this->getRed("Error ({$code}):").PHP_EOL);
        foreach ($errors as $error) {
            fwrite(STDERR, $this->getRed("  - {$error}").PHP_EOL);
        }
    }
}
