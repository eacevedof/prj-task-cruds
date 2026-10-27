import { LanguageCodeEnum } from "#app/Modules/Shared/Domain/Enums/LanguageCodeEnum.ts";
import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { HttpRequestMock } from "#tests/Modules/Shared/Infrastructure/Components/HttpRequestMock.ts";
import {
    RequestMetadataTestCaseEnum,
    type RequestMetadataTestCase,
} from "#tests/Modules/Shared/Infrastructure/Components/Http/HttpRequest/Mothers/RequestMetadataTestCaseEnum.ts";

const IP_ADDRESS = "203.0.113.7";

const UA_CHROME_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36";
const UA_SAFARI_IPHONE = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1";
const UA_EDGE_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.3537.57";
const UA_CHROME_ANDROID = "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36";
const UA_CURL = "curl/8.10.1";
const UA_FIREFOX_LINUX = "Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0";

/**
 * Peticiones con cabeceras de navegadores reales y la metadata que se espera de cada una.
 */
export class RequestMetadataRequestMother {

    static getInstance(): RequestMetadataRequestMother {
        return new RequestMetadataRequestMother();
    }

    loadRequestByTestCase(useCase: RequestMetadataTestCase): void {
        const httpRequestMock = HttpRequestMock.getInstance().setIpAddress(IP_ADDRESS);
        switch (useCase) {
            case RequestMetadataTestCaseEnum.OK_AS_CHROME_ON_WINDOWS_IN_SPANISH:
                httpRequestMock.setHeaders({
                    "User-Agent": UA_CHROME_WINDOWS,
                    "Accept-Language": "es-ES,es;q=0.9,en;q=0.8",
                    "X-Time-Zone": "Europe/Madrid",
                });
                break;

            case RequestMetadataTestCaseEnum.OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH:
                httpRequestMock.setHeaders({
                    "User-Agent": UA_SAFARI_IPHONE,
                    "Accept-Language": "en-US,en;q=0.9",
                    "X-Time-Zone": "America/New_York",
                });
                break;

            case RequestMetadataTestCaseEnum.OK_AS_EDGE_NOT_CHROME_ON_WINDOWS:
                httpRequestMock.setHeaders({ "User-Agent": UA_EDGE_WINDOWS });
                break;

            case RequestMetadataTestCaseEnum.OK_AS_ANDROID_NOT_LINUX:
                httpRequestMock.setHeaders({ "User-Agent": UA_CHROME_ANDROID });
                break;

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS:
                httpRequestMock.setHeaders({ "User-Agent": UA_CURL });
                break;

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE:
                httpRequestMock.setHeaders({
                    "User-Agent": UA_FIREFOX_LINUX,
                    "Accept-Language": "fr-FR,fr;q=0.9",
                    "X-Time-Zone": "Mars/Olympus_Mons",
                });
                break;
        }
        httpRequestMock.getMockedInstance();
    }

    /**
     * Metadata esperada, con las claves de AbstractInputDto.
     */
    getExpectedMetadataByTestCase(useCase: RequestMetadataTestCase): Primitives {
        switch (useCase) {
            case RequestMetadataTestCaseEnum.OK_AS_CHROME_ON_WINDOWS_IN_SPANISH:
                return this.#getMetadata("Chrome", "141.0.0.0", "Windows", "Europe/Madrid", LanguageCodeEnum.ES);

            case RequestMetadataTestCaseEnum.OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH:
                return this.#getMetadata("Safari", "18.5", "iOS", "America/New_York", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_EDGE_NOT_CHROME_ON_WINDOWS:
                return this.#getMetadata("Edge", "141.0.3537.57", "Windows", "", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_ANDROID_NOT_LINUX:
                return this.#getMetadata("Chrome", "141.0.0.0", "Android", "", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS:
                return this.#getMetadata("curl", "8.10.1", "", "", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE:
                return this.#getMetadata("Firefox", "144.0", "Linux", "", LanguageCodeEnum.EN);
        }
    }

    #getMetadata(
        userBrowser: string,
        userBrowserVersion: string,
        userOs: string,
        userTimeZone: string,
        userLanguageCode: string
    ): Primitives {
        return {
            userIpAddress: IP_ADDRESS,
            userBrowser: userBrowser,
            userBrowserVersion: userBrowserVersion,
            userOs: userOs,
            userTimeZone: userTimeZone,
            userLanguageCode: userLanguageCode,
        };
    }
}
