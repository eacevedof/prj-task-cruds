import { runWithoutDb } from "#tests/bootstrap.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

import { AbstractTestCase } from "#tests/AbstractTestCase.ts";
import { DocumentationControllerMother } from "#tests/Modules/Open/Documentation/Infrastructure/Controllers/DocumentationController/Mothers/DocumentationControllerMother.ts";
import {
    DocumentationTestCaseEnum,
    type DocumentationTestCase,
} from "#tests/Modules/Open/Documentation/Infrastructure/Controllers/DocumentationController/Mothers/DocumentationTestCaseEnum.ts";

const UNREPLACED_PLACEHOLDER = "{{";

runWithoutDb();

/**
 * La documentacion es publica y sale de los .md: sin token, 200, HTML y sin {{placeholders}} sin rellenar.
 */
class DocumentationControllerTest extends AbstractTestCase {

    testOkAsSuccessByDocumentationWithoutToken(): void {
        const httpResponse = DocumentationControllerMother.getControllerInstance().show();
        this.#assertPublishedHtmlPage(
            httpResponse,
            DocumentationTestCaseEnum.OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN
        );
        this.assertStringContainsString(
            EnvironmentReaderRawRepository.getInstance().getAppVersion(),
            httpResponse.getBody()
        );
        this.assertStringContainsString("<table class=\"md-table\">", httpResponse.getBody());
    }

    testOkAsSuccessByChangelogWithoutToken(): void {
        const httpResponse = DocumentationControllerMother.getChangelogControllerInstance().show();

        this.#assertPublishedHtmlPage(
            httpResponse,
            DocumentationTestCaseEnum.OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN
        );
        this.assertStringContainsString(
            `id="${EnvironmentReaderRawRepository.getInstance().getAppVersion()}"`,
            httpResponse.getBody()
        );
    }

    #assertPublishedHtmlPage(httpResponse: HttpResponder, documentationTestCaseEnum: DocumentationTestCase): void {
        this.assertEquals(ExceptionEnum.CODE_OK, httpResponse.getStatusCode(), documentationTestCaseEnum);
        this.assertStringStartsWith("text/html", httpResponse.getHeaders()["Content-Type"] ?? "");
        this.assertStringNotContainsString(UNREPLACED_PLACEHOLDER, httpResponse.getBody());
    }
}

AbstractTestCase.runTests(DocumentationControllerTest);
