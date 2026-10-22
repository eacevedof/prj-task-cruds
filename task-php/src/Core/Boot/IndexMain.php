<?php

declare(strict_types=1);

namespace App\Core\Boot;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;

/**
 * Front controller de la API (public/index.php). Sin sesion ni vistas: solo JSON.
 */
final class IndexMain
{
    private array $routes;

    public function __construct()
    {
        $this->routes = include_once BootPathsEnum::PATH_SRC."/Core/routes/routes.php";
        $this->loadCorsHeaders();
        $this->loadSecurityHeaders();
        $this->loadErrorDisplay();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    public function exec(): void
    {
        $requestMethod = strtolower((string) ($_SERVER["REQUEST_METHOD"] ?? "get"));
        if ($requestMethod === "options") {
            HttpResponse::noContent()->send();
            return;
        }

        $runData = ApiRouter::getInstance($this->routes)
            ->getRunData($requestMethod, (string) ($_SERVER["REQUEST_URI"] ?? "/"));
        $this->routes = [];

        if (!$runData) {
            $this->getErrorResponse(ExceptionEnum::CODE_NOT_FOUND, "Resource not found")->send();
            return;
        }

        if (!isset($runData["controller"])) {
            $this->getErrorResponse(
                ExceptionEnum::CODE_METHOD_NOT_ALLOWED,
                "Method not allowed",
                ["Allow" => strtoupper(implode(", ", $runData["allowed"]))]
            )->send();
            return;
        }

        $this->loadJsonBodyIntoPost();

        $controller = new $runData["controller"]();
        /** @var HttpResponse $httpResponse */
        $httpResponse = $controller->{$runData["method"]}(...$runData["_args"]);
        $httpResponse->send();
    }

    public static function onError(Throwable $ex): void
    {
        $uuid = uniqid();
        $sensitiveDataMasker = SensitiveDataMasker::getInstance();
        if ($_POST) {
            lgerr($sensitiveDataMasker->getMaskedArray($_POST), "index-exception $uuid POST", "error");
        }
        if ($_GET) {
            lgerr($sensitiveDataMasker->getMaskedArray($_GET), "index-exception $uuid GET", "error");
        }
        lgerr($ex->getMessage(), "index-exception $uuid", "error");
        lgerr($ex->getTraceAsString(), "index-exception $uuid TRACE", "error");
        lgerr($ex->getFile()." : (line: {$ex->getLine()})", "file-line $uuid", "error");

        HttpResponse::json(
            (string) json_encode([
                "code" => ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                "status" => false,
                "errors" => ["Sorry! but some unexpected error occurred ($uuid)"],
                "data" => [],
            ]),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
        )->send();
    }

    public static function debug(Throwable $ex): void
    {
        $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
        if ($environmentReaderRawRepository->isProduction()) return;
        if (!$environmentReaderRawRepository->isDebug()) return;

        lgerr([
            "Exception" => $ex->getMessage(),
            "File" => $ex->getFile()."(".$ex->getLine().")",
            "POST" => $_POST,
            "GET" => $_GET,
        ], "index-debug", "debug");
    }

    /**
     * PHP solo rellena $_POST con form-data en POST. La API habla JSON (tambien en PUT/PATCH/DELETE):
     * se decodifica php://input y, si no es un objeto JSON, $_POST queda vacio.
     */
    private function loadJsonBodyIntoPost(): void
    {
        if ($_POST) return;
        if (!$json = (string) file_get_contents("php://input")) return;

        $body = json_decode($json, true);
        $_POST = is_array($body) ? $body : [];
    }

    private function getErrorResponse(int $code, string $error, array $headers = []): HttpResponse
    {
        return HttpResponse::json(
            (string) json_encode(["code" => $code, "status" => false, "errors" => [$error], "data" => []]),
            $code,
            $headers
        );
    }

    private function loadCorsHeaders(): void
    {
        if (isset($_SERVER["HTTP_ORIGIN"])) {
            header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
            header("Access-Control-Max-Age: 86400");
        }
        if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Accept");
        }
    }

    private function loadSecurityHeaders(): void
    {
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: no-referrer");
    }

    private function loadErrorDisplay(): void
    {
        if (!EnvironmentReaderRawRepository::getInstance()->isProduction()) return;

        ini_set("display_errors", "0");
        ini_set("log_errors", "1");
        ini_set("error_log", BootPathsEnum::PATH_LOGS."/".date("Y-m-d")."-sys.log");
    }
}
