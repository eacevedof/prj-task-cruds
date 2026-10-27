import { runWithoutDb } from "#tests/bootstrap.ts";

import { LanguageCodeEnum } from "#app/Modules/Shared/Domain/Enums/LanguageCodeEnum.ts";
import { HttpRequester } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequester.ts";

import { GetUserDto } from "#app/Modules/Users/Application/GetUser/GetUserDto.ts";

import { AbstractTestCase } from "#tests/AbstractTestCase.ts";
import { RequestMetadataRequestMother } from "#tests/Modules/Shared/Infrastructure/Components/Http/HttpRequest/Mothers/RequestMetadataRequestMother.ts";
import {
    RequestMetadataTestCaseEnum,
    type RequestMetadataTestCase,
} from "#tests/Modules/Shared/Infrastructure/Components/Http/HttpRequest/Mothers/RequestMetadataTestCaseEnum.ts";

runWithoutDb();

/**
 * Cabeceras de la peticion -> metadata (HttpRequester) -> dto de entrada (AbstractInputDto): lo que
 * le llega a la logica de negocio.
 */
class HttpRequestTest extends AbstractTestCase {

    static getDataProviders(): Record<string, () => Record<string, unknown[]>> {
        return {
            testMetadataReachesInputDto: HttpRequestTest.getTestCases,
        };
    }

    static getTestCases(): Record<string, unknown[]> {
        return Object.fromEntries(
            Object.values(RequestMetadataTestCaseEnum).map((requestMetadataTestCaseEnum) => [
                requestMetadataTestCaseEnum,
                [requestMetadataTestCaseEnum],
            ])
        );
    }

    testMetadataReachesInputDto(requestMetadataTestCaseEnum: RequestMetadataTestCase): void {
        const requestMetadataRequestMother = RequestMetadataRequestMother.getInstance();
        requestMetadataRequestMother.loadRequestByTestCase(requestMetadataTestCaseEnum);

        const requestMetadata = HttpRequester.getInstance().getMetadata();
        const expectedMetadata = requestMetadataRequestMother.getExpectedMetadataByTestCase(requestMetadataTestCaseEnum);
        this.assertSame(expectedMetadata, requestMetadata);

        const getUserDto = GetUserDto.fromPrimitives({ ...requestMetadata, userUuid: "any-uuid" });
        this.assertSame(expectedMetadata.userIpAddress, getUserDto.getUserIpAddress());
        this.assertSame(expectedMetadata.userBrowser, getUserDto.getUserBrowser());
        this.assertSame(expectedMetadata.userBrowserVersion, getUserDto.getUserBrowserVersion());
        this.assertSame(expectedMetadata.userOs, getUserDto.getUserOs());
        this.assertSame(expectedMetadata.userTimeZone, getUserDto.getUserTimeZone());
        this.assertSame(expectedMetadata.userLanguageCode, getUserDto.getUserLanguageCode());
        this.assertSame("any-uuid", getUserDto.getUserUuid());
    }

    testDtoWithoutMetadataFallsBackToDefaultLanguage(): void {
        // desde CLI o un test no hay peticion: metadata vacia, idioma por defecto
        const getUserDto = GetUserDto.fromPrimitives({ userUuid: "any-uuid" });

        this.assertSame("", getUserDto.getUserIpAddress());
        this.assertSame(LanguageCodeEnum.EN, getUserDto.getUserLanguageCode());
    }
}

AbstractTestCase.runTests(HttpRequestTest);
