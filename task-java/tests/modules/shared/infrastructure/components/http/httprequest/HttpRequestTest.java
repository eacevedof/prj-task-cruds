package modules.shared.infrastructure.components.http.httprequest;

import java.util.Map;
import java.util.stream.Stream;

import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.MethodSource;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.LanguageCodeEnum;
import modules.shared.infrastructure.components.http.httprequest.mothers.RequestMetadataRequestMother;
import modules.shared.infrastructure.components.http.httprequest.mothers.RequestMetadataTestCaseEnum;
import modules.shared.infrastructure.components.httprequester.HttpRequester;
import modules.users.application.getuser.GetUserDto;

import testcase.AbstractTestCase;
import testcase.Bootstrap;

/**
 * Cabeceras de la peticion -> metadata (HttpRequester) -> dto de entrada (AbstractInputDto): lo que
 * le llega a la logica de negocio.
 */
class HttpRequestTest extends AbstractTestCase {

    @BeforeAll
    static void setUpTestDb() {
        Bootstrap.runWithoutDb();
    }

    static Stream<String> getTestCases() {
        return RequestMetadataTestCaseEnum.VALUES.stream();
    }

    @ParameterizedTest
    @MethodSource("getTestCases")
    void testMetadataReachesInputDto(String requestMetadataTestCaseEnum) {
        RequestMetadataRequestMother requestMetadataRequestMother = RequestMetadataRequestMother.getInstance();
        requestMetadataRequestMother.loadRequestByTestCase(requestMetadataTestCaseEnum);

        Map<String, Object> requestMetadata = HttpRequester.getInstance().getMetadata();
        Map<String, Object> expectedMetadata = requestMetadataRequestMother.getExpectedMetadataByTestCase(requestMetadataTestCaseEnum);
        assertSame(expectedMetadata, requestMetadata);

        GetUserDto getUserDto = GetUserDto.fromPrimitives(Primitives.merge(requestMetadata, Primitives.of("userUuid", "any-uuid")));
        assertSame(expectedMetadata.get("userIpAddress"), getUserDto.getUserIpAddress());
        assertSame(expectedMetadata.get("userBrowser"), getUserDto.getUserBrowser());
        assertSame(expectedMetadata.get("userBrowserVersion"), getUserDto.getUserBrowserVersion());
        assertSame(expectedMetadata.get("userOs"), getUserDto.getUserOs());
        assertSame(expectedMetadata.get("userTimeZone"), getUserDto.getUserTimeZone());
        assertSame(expectedMetadata.get("userLanguageCode"), getUserDto.getUserLanguageCode());
        assertSame("any-uuid", getUserDto.getUserUuid());
    }

    @Test
    void testDtoWithoutMetadataFallsBackToDefaultLanguage() {
        // desde CLI o un test no hay peticion: metadata vacia, idioma por defecto
        GetUserDto getUserDto = GetUserDto.fromPrimitives(Primitives.of("userUuid", "any-uuid"));

        assertSame("", getUserDto.getUserIpAddress());
        assertSame(LanguageCodeEnum.EN, getUserDto.getUserLanguageCode());
    }
}
