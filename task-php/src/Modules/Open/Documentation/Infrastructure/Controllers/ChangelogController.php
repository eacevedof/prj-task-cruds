<?php
declare(strict_types=1);

namespace App\Modules\Open\Documentation\Infrastructure\Controllers;

use Throwable;

use App\Modules\Open\Documentation\Infrastructure\Views\ChangelogView;
use App\Modules\Open\Documentation\Infrastructure\Views\DocumentationViewDto;
use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;

/**
 * GET /api/v1/changelog  (publica, HTML generado desde changelog.md)
 */
final class ChangelogController
{
    use LogTrait;

    public function show(): HttpResponder
    {
        try {
            return HttpResponder::html(
                ChangelogView::getInstance()->getHtml(
                    DocumentationViewDto::fromPrimitives([
                        "appBaseUrl" => EnvironmentReaderRawRepository::getInstance()->getBaseUrl(),
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
