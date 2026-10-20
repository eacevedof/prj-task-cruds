<?php

declare(strict_types=1);

namespace Tests\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Repositories\Configuration\EnvironmentReaderRawRepository;
use Tests\AbstractTestCase;
use Tests\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController\Mothers\{
    DocumentationControllerMother, DocumentationTestCaseEnum};

/**
 * La documentacion es publica y sale de los .md: sin token, 200, HTML y sin {{placeholders}} sin rellenar.
 */
final class DocumentationControllerTest extends AbstractTestCase
{
    private const string UNREPLACED_PLACEHOLDER = "{{";

    public function testOkAsSuccessByDocumentationWithoutToken(): void
    {
        $httpResponse = DocumentationControllerMother::getControllerInstance()->show();
        $this->assertPublishedHtmlPage(
            $httpResponse,
            DocumentationTestCaseEnum::OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN
        );
        $this->assertStringContainsString(
            EnvironmentReaderRawRepository::getInstance()->getAppVersion(),
            $httpResponse->getBody()
        );
        $this->assertStringContainsString("<table class=\"md-table\">", $httpResponse->getBody());
    }

    public function testOkAsSuccessByChangelogWithoutToken(): void
    {
        $httpResponse = DocumentationControllerMother::getChangelogControllerInstance()->show();

        $this->assertPublishedHtmlPage(
            $httpResponse,
            DocumentationTestCaseEnum::OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN
        );
        $this->assertStringContainsString(
            "id=\"".EnvironmentReaderRawRepository::getInstance()->getAppVersion()."\"",
            $httpResponse->getBody()
        );
    }

    private function assertPublishedHtmlPage(HttpResponder $httpResponse, DocumentationTestCaseEnum $documentationTestCaseEnum): void
    {
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode(), $documentationTestCaseEnum->name);
        $this->assertStringStartsWith("text/html", $httpResponse->getHeaders()["Content-Type"] ?? "");
        $this->assertStringNotContainsString(self::UNREPLACED_PLACEHOLDER, $httpResponse->getBody());
    }
}
