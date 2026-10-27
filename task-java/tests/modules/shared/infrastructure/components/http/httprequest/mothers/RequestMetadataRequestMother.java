package modules.shared.infrastructure.components.http.httprequest.mothers;

import java.util.LinkedHashMap;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.LanguageCodeEnum;
import modules.shared.infrastructure.components.HttpRequestMock;

/**
 * Peticiones con cabeceras de navegadores reales y la metadata que se espera de cada una.
 */
public final class RequestMetadataRequestMother {

    private static final String IP_ADDRESS = "203.0.113.7";

    private static final String UA_CHROME_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36";
    private static final String UA_SAFARI_IPHONE = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1";
    private static final String UA_EDGE_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.3537.57";
    private static final String UA_CHROME_ANDROID = "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36";
    private static final String UA_CURL = "curl/8.10.1";
    private static final String UA_FIREFOX_LINUX = "Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0";

    public static RequestMetadataRequestMother getInstance() {
        return new RequestMetadataRequestMother();
    }

    public void loadRequestByTestCase(String useCase) {
        HttpRequestMock httpRequestMock = HttpRequestMock.getInstance().setIpAddress(IP_ADDRESS);
        switch (useCase) {
            case RequestMetadataTestCaseEnum.OK_AS_CHROME_ON_WINDOWS_IN_SPANISH -> httpRequestMock.setHeaders(getHeaders(
                "User-Agent", UA_CHROME_WINDOWS,
                "Accept-Language", "es-ES,es;q=0.9,en;q=0.8",
                "X-Time-Zone", "Europe/Madrid"
            ));

            case RequestMetadataTestCaseEnum.OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH -> httpRequestMock.setHeaders(getHeaders(
                "User-Agent", UA_SAFARI_IPHONE,
                "Accept-Language", "en-US,en;q=0.9",
                "X-Time-Zone", "America/New_York"
            ));

            case RequestMetadataTestCaseEnum.OK_AS_EDGE_NOT_CHROME_ON_WINDOWS ->
                httpRequestMock.setHeaders(getHeaders("User-Agent", UA_EDGE_WINDOWS));

            case RequestMetadataTestCaseEnum.OK_AS_ANDROID_NOT_LINUX ->
                httpRequestMock.setHeaders(getHeaders("User-Agent", UA_CHROME_ANDROID));

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS ->
                httpRequestMock.setHeaders(getHeaders("User-Agent", UA_CURL));

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE -> httpRequestMock.setHeaders(getHeaders(
                "User-Agent", UA_FIREFOX_LINUX,
                "Accept-Language", "fr-FR,fr;q=0.9",
                "X-Time-Zone", "Mars/Olympus_Mons"
            ));

            default -> throw new IllegalArgumentException("unknown test case " + useCase);
        }
        httpRequestMock.getMockedInstance();
    }

    /**
     * Metadata esperada, con las claves de AbstractInputDto.
     */
    public Map<String, Object> getExpectedMetadataByTestCase(String useCase) {
        return switch (useCase) {
            case RequestMetadataTestCaseEnum.OK_AS_CHROME_ON_WINDOWS_IN_SPANISH ->
                getMetadata("Chrome", "141.0.0.0", "Windows", "Europe/Madrid", LanguageCodeEnum.ES);

            case RequestMetadataTestCaseEnum.OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH ->
                getMetadata("Safari", "18.5", "iOS", "America/New_York", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_EDGE_NOT_CHROME_ON_WINDOWS ->
                getMetadata("Edge", "141.0.3537.57", "Windows", "", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_ANDROID_NOT_LINUX ->
                getMetadata("Chrome", "141.0.0.0", "Android", "", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS ->
                getMetadata("curl", "8.10.1", "", "", LanguageCodeEnum.EN);

            case RequestMetadataTestCaseEnum.OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE ->
                getMetadata("Firefox", "144.0", "Linux", "", LanguageCodeEnum.EN);

            default -> throw new IllegalArgumentException("unknown test case " + useCase);
        };
    }

    private Map<String, Object> getMetadata(
        String userBrowser,
        String userBrowserVersion,
        String userOs,
        String userTimeZone,
        String userLanguageCode
    ) {
        return Primitives.of(
            "userIpAddress", IP_ADDRESS,
            "userBrowser", userBrowser,
            "userBrowserVersion", userBrowserVersion,
            "userOs", userOs,
            "userTimeZone", userTimeZone,
            "userLanguageCode", userLanguageCode
        );
    }

    /**
     * Cabeceras con su nombre HTTP, en orden: nombre, valor, nombre, valor...
     */
    private Map<String, String> getHeaders(String... namesAndValues) {
        Map<String, String> headers = new LinkedHashMap<>();
        for (int i = 0; i + 1 < namesAndValues.length; i += 2) {
            headers.put(namesAndValues[i], namesAndValues[i + 1]);
        }
        return headers;
    }
}
