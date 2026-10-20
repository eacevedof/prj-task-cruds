<?php

declare(strict_types=1);

namespace App\Core\Boot;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpRequester\HttpRequester;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;

/**
 * Front controller de la API (public/index.php). Sin sesion ni vistas: solo JSON.
 */
final class IndexMain
{
    private array $routes;
    private HttpRequester $httpRequest;

    public function __construct()
    {
        $this->httpRequest = HttpRequester::getInstance();
        $this->routes = include_once BootPathsEnum::PATH_SRC."/Core/routes/routes.php";
        $this->loadErrorDisplay();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    private function loadErrorDisplay(): void
    {
        if (!EnvironmentReaderRawRepository::getInstance()->isProduction())
            return;

        ini_set("display_errors", "0");
        ini_set("log_errors", "1");
        ini_set("error_log", BootPathsEnum::PATH_LOGS."/".date("Y-m-d")."-sys.log");
    }

    public function exec(): void
    {
        $requestMethod = $this->httpRequest->getRequestMethod();
        if ($requestMethod === "options") {
            self::echoWithCommonHeaders(HttpResponder::noContent());
            return;
        }

        $runData = ApiRouter::getInstance($this->routes)->getRunData(
            $requestMethod,
            $this->httpRequest->getRequestUri()
        );

        $this->routes = [];
        if (!$runData) {
            self::echoWithCommonHeaders($this->getErrorResponse(
                ExceptionEnum::CODE_NOT_FOUND,
                "Resource not found"
            ));
            return;
        }

        if (!isset($runData["controller"])) {
            self::echoWithCommonHeaders($this->getErrorResponse(
                ExceptionEnum::CODE_METHOD_NOT_ALLOWED,
                "Method not allowed",
                ["Allow" => strtoupper(implode(", ", $runData["allowed"]))]
            ));
            return;
        }

        $this->httpRequest->loadJsonBodyIntoPost();

        $controller = new $runData["controller"]();
        /** @var HttpResponder $httpResponse */
        $httpResponse = $controller->{$runData["method"]}(...$runData["_args"]);
        self::echoWithCommonHeaders($httpResponse);
    }

    /**
     * Toda respuesta sale por aqui: las cabeceras comunes se suman a las del HttpResponder y se emiten juntas
     * en echoHeadersAndBody(), el unico punto que hace header()/echo.
     */
    private static function echoWithCommonHeaders(HttpResponder $httpResponse): void
    {
        $httpResponse->getWithHeaders([
            ...self::getCorsHeaders(),
            ...self::getSecurityHeaders(),
        ])->echoHeadersAndBody();
    }

    /**
     * @return array<string,string>
     */
    private static function getCorsHeaders(): array
    {
        $httpRequest = HttpRequester::getInstance();
        $corsHeaders = [];
        if ($origin = $httpRequest->getHeaderValueByKey("Origin")) {
            $corsHeaders["Access-Control-Allow-Origin"] = $origin;
            $corsHeaders["Access-Control-Max-Age"] = "86400";
        }
        if ($httpRequest->getRequestMethod() === "options") {
            $corsHeaders["Access-Control-Allow-Methods"] = "GET, POST, PUT, DELETE, OPTIONS";
            $corsHeaders["Access-Control-Allow-Headers"] = "Content-Type, Accept, Accept-Language, Authorization, X-Time-Zone";
        }
        return $corsHeaders;
    }

    /**
     * @return array<string,string>
     */
    private static function getSecurityHeaders(): array
    {
        return [
            "X-Content-Type-Options" => "nosniff",
            "Referrer-Policy" => "no-referrer",
        ];
    }

    public static function onError(Throwable $ex): void
    {
        $uuid = uniqid();
        $sensitiveDataMasker = SensitiveDataMasker::getInstance();
        $httpRequest = HttpRequester::getInstance();
        if ($post = $httpRequest->getPost()) {
            lgerr($sensitiveDataMasker->getMaskedArray($post), "index-exception $uuid POST", "error");
        }
        if ($get = $httpRequest->getGet()) {
            lgerr($sensitiveDataMasker->getMaskedArray($get), "index-exception $uuid GET", "error");
        }
        lgerr($ex->getMessage(), "index-exception $uuid", "error");
        lgerr($ex->getTraceAsString(), "index-exception $uuid TRACE", "error");
        lgerr($ex->getFile()." : (line: {$ex->getLine()})", "file-line $uuid", "error");

        self::echoWithCommonHeaders(HttpResponder::json(
            (string) json_encode([
                "code" => ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                "status" => false,
                "errors" => ["Sorry! but some unexpected error occurred ($uuid)"],
                "data" => [],
            ]),
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
        ));
    }

    public static function debug(Throwable $ex): void
    {
        $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
        if ($environmentReaderRawRepository->isProduction()) return;
        if (!$environmentReaderRawRepository->isDebug()) return;

        lgerr([
            "Exception" => $ex->getMessage(),
            "File" => $ex->getFile()."(".$ex->getLine().")",
            "POST" => HttpRequester::getInstance()->getPost(),
            "GET" => HttpRequester::getInstance()->getGet(),
        ], "index-debug", "debug");
    }

    private function getErrorResponse(int $code, string $error, array $headers = []): HttpResponder
    {
        return HttpResponder::json(
            (string) json_encode([
                "code" => $code,
                "status" => false,
                "errors" => [$error],
                "data" => []
            ]),
            $code,
            $headers
        );
    }

}
