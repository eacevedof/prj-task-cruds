<?php
declare(strict_types=1);

namespace App\Modules\Open\Documentation\Infrastructure\Views;

use App\Modules\Shared\Infrastructure\Components\Markdown\Markdowner;
use App\Modules\Shared\Infrastructure\Views\AbstractView;

/**
 * Publica changelog.md (una seccion por version; el titulo de la doc enlaza a #<version>).
 */
final class ChangelogView extends AbstractView
{
    private const string TITLE = "Task PHP CRUD API - changelog";

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
        $markdown = $this->getReplacedContent((string) file_get_contents(__DIR__."/changelog.md"), [
            "{{appBaseUrl}}" => $documentationViewDto->getAppBaseUrl(),
        ]);
        return $this->getHtmlPage(self::TITLE, $this->markdowner->getHtmlFromMarkdown($markdown));
    }
}
