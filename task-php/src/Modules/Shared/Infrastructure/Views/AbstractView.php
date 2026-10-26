<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Views;

/**
 * Esqueleto HTML de las paginas que se generan desde un .md (patron Documentation de app-ms-antiphising).
 */
abstract class AbstractView
{
    private const string CSS_PATH = "/assets/Modules/Documentation/Css/documentation.css";
    private const string JS_PATH = "/assets/Modules/Documentation/Js/documentation.js";

    protected function getHtmlPage(string $title, string $htmlBody): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES, "UTF-8");
        $cssPath = self::CSS_PATH;
        $jsPath = self::JS_PATH;
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{$title}</title>
        <link rel="stylesheet" href="{$cssPath}">
        </head>
        <body>
        {$htmlBody}
        <script src="{$jsPath}"></script>
        </body>
        </html>
        HTML;
    }

    /**
     * Rellena los {{placeholder}} del .md: ["{{appVersion}}" => "1.0.0", ...]
     */
    protected function getReplacedContent(string $content, array $toReplace): string
    {
        return strtr($content, $toReplace);
    }
}
