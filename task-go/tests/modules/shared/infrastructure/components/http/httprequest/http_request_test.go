package httprequest

import (
	"os"
	"testing"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/users/application/getuser"
	"task-go/tests"
	"task-go/tests/modules/shared/infrastructure/components/http/httprequest/mothers"
)

// Cabeceras de la peticion -> metadata (HttpRequester) -> dto de entrada (AbstractInputDto): lo que le llega a la
// logica de negocio.

func TestMain(m *testing.M) {
	os.Exit(tests.RunWithoutDb(m))
}

func TestMetadataReachesInputDto(t *testing.T) {
	for _, requestMetadataTestCaseEnum := range mothers.GetTestCases() {
		t.Run(string(requestMetadataTestCaseEnum), func(t *testing.T) {
			requestMetadataRequestMother := mothers.RequestMetadataRequestMotherGetInstance()
			httpRequester := requestMetadataRequestMother.GetRequestByTestCase(requestMetadataTestCaseEnum)

			requestMetadata := httpRequester.GetMetadata()
			expectedMetadata := requestMetadataRequestMother.GetExpectedMetadataByTestCase(requestMetadataTestCaseEnum)
			tests.AssertEquals(t, expectedMetadata, requestMetadata)

			getUserDto := getuser.GetUserDtoFromPrimitives(application.MergePrimitives(
				requestMetadata,
				map[string]any{"userUuid": "any-uuid"},
			))
			tests.AssertEquals(t, expectedMetadata["userIpAddress"], any(getUserDto.GetUserIpAddress()))
			tests.AssertEquals(t, expectedMetadata["userBrowser"], any(getUserDto.GetUserBrowser()))
			tests.AssertEquals(t, expectedMetadata["userBrowserVersion"], any(getUserDto.GetUserBrowserVersion()))
			tests.AssertEquals(t, expectedMetadata["userOs"], any(getUserDto.GetUserOs()))
			tests.AssertEquals(t, expectedMetadata["userTimeZone"], any(getUserDto.GetUserTimeZone()))
			tests.AssertEquals(t, expectedMetadata["userLanguageCode"], any(getUserDto.GetUserLanguageCode()))
			tests.AssertEquals(t, "any-uuid", getUserDto.GetUserUuid())
		})
	}
}

func TestDtoWithoutMetadataFallsBackToDefaultLanguage(t *testing.T) {
	// desde CLI o un test no hay peticion: metadata vacia, idioma por defecto
	getUserDto := getuser.GetUserDtoFromPrimitives(map[string]any{"userUuid": "any-uuid"})

	tests.AssertEquals(t, "", getUserDto.GetUserIpAddress())
	tests.AssertEquals(t, enums.LanguageCodeEnum.EN, getUserDto.GetUserLanguageCode())
}
