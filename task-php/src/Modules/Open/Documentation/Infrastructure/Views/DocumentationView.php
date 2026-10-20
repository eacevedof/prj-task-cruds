<?php
declare(strict_types=1);

namespace App\Modules\Open\Documentation\Infrastructure\Views;

use App\Modules\Shared\Infrastructure\Components\Markdown\Markdowner;
use App\Modules\Shared\Infrastructure\Views\AbstractView;

/**
 * Publica documentation.md: TODA la documentacion de la API vive en ese .md (aqui no hay texto).
 */
final class DocumentationView extends AbstractView
{
    private const string TITLE = "Task PHP CRUD API";

    private Markdowner $markdowner;

    public function __construct()
    {
        $this->markdowner = Markdowner::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    public function getHtml(DocumentationViewDto $documentationViewDto): string
    {
        $markdown = $this->getReplacedContent((string) file_get_contents(__DIR__."/documentation.md"), [
            "{{appVersion}}" => $documentationViewDto->getAppVersion(),
            "{{appVersionUpdate}}" => $documentationViewDto->getAppVersionUpdate(),
            "{{appBaseUrl}}" => $documentationViewDto->getAppBaseUrl(),
        ]);
        return $this->getHtmlPage(self::TITLE, $this->markdowner->getHtmlFromMarkdown($markdown));
    }
}
