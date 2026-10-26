<?php
declare(strict_types=1);

namespace App\Modules\Open\Documentation\Infrastructure\Controllers;

use Throwable;

use App\Modules\Open\Documentation\Infrastructure\Views\DocumentationView;
use App\Modules\Open\Documentation\Infrastructure\Views\DocumentationViewDto;
use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;

/**
 * GET /api/v1  (publica, HTML generado desde documentation.md)
 */
final class DocumentationController
{
    use LogTrait;

    public function show(): HttpResponder
    {
        try {
            $environmentReaderRawRepository = EnvironmentReaderRawRepository::getInstance();
            return HttpResponder::html(
                DocumentationView::getInstance()->getHtml(
                    DocumentationViewDto::fromPrimitives([
                        "appVersion" => $environmentReaderRawRepository->getAppVersion(),
                        "appVersionUpdate" => $environmentReaderRawRepository->getAppVersionUpdate(),
                        "appBaseUrl" => $environmentReaderRawRepository->getBaseUrl(),
                    ])
                )
            );
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
            return HttpResponder::html(
                "<h1>".__(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)."</h1>",
                ExceptionEnum::CODE_INTERNAL_SERVER_ERROR
            );
        }
    }
}
