package mothers

import (
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/tests/modules/shared/infrastructure/components"
)

type RequestMetadataTestCaseEnum string

const (
	OK_AS_CHROME_ON_WINDOWS_IN_SPANISH                           RequestMetadataTestCaseEnum = "OK_AS_CHROME_ON_WINDOWS_IN_SPANISH"
	OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH                            RequestMetadataTestCaseEnum = "OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH"
	OK_AS_EDGE_NOT_CHROME_ON_WINDOWS                             RequestMetadataTestCaseEnum = "OK_AS_EDGE_NOT_CHROME_ON_WINDOWS"
	OK_AS_ANDROID_NOT_LINUX                                      RequestMetadataTestCaseEnum = "OK_AS_ANDROID_NOT_LINUX"
	OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS                       RequestMetadataTestCaseEnum = "OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS"
	OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE RequestMetadataTestCaseEnum = "OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE"
)

// GetTestCases todos los casos, en el orden del enum (el DataProvider de task-php).
func GetTestCases() []RequestMetadataTestCaseEnum {
	return []RequestMetadataTestCaseEnum{
		OK_AS_CHROME_ON_WINDOWS_IN_SPANISH,
		OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH,
		OK_AS_EDGE_NOT_CHROME_ON_WINDOWS,
		OK_AS_ANDROID_NOT_LINUX,
		OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS,
		OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE,
	}
}

const (
	ipAddress = "203.0.113.7"

	uaChromeWindows = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36"
	uaSafariIphone  = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1"
	uaEdgeWindows   = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.3537.57"
	uaChromeAndroid = "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36"
	uaCurl          = "curl/8.10.1"
	uaFirefoxLinux  = "Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0"
)

// RequestMetadataRequestMother peticiones con cabeceras de navegadores reales y la metadata que se espera de cada una.
type RequestMetadataRequestMother struct{}

func RequestMetadataRequestMotherGetInstance() *RequestMetadataRequestMother {
	return &RequestMetadataRequestMother{}
}

func (m *RequestMetadataRequestMother) GetRequestByTestCase(useCase RequestMetadataTestCaseEnum) *httprequester.HttpRequester {
	httpRequestMock := components.GetInstance().SetIpAddress(ipAddress)
	switch useCase {
	case OK_AS_CHROME_ON_WINDOWS_IN_SPANISH:
		httpRequestMock.SetHeaders(map[string]string{
			"User-Agent":      uaChromeWindows,
			"Accept-Language": "es-ES,es;q=0.9,en;q=0.8",
			"X-Time-Zone":     "Europe/Madrid",
		})
	case OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH:
		httpRequestMock.SetHeaders(map[string]string{
			"User-Agent":      uaSafariIphone,
			"Accept-Language": "en-US,en;q=0.9",
			"X-Time-Zone":     "America/New_York",
		})
	case OK_AS_EDGE_NOT_CHROME_ON_WINDOWS:
		httpRequestMock.SetHeaders(map[string]string{"User-Agent": uaEdgeWindows})
	case OK_AS_ANDROID_NOT_LINUX:
		httpRequestMock.SetHeaders(map[string]string{"User-Agent": uaChromeAndroid})
	case OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS:
		httpRequestMock.SetHeaders(map[string]string{"User-Agent": uaCurl})
	case OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE:
		httpRequestMock.SetHeaders(map[string]string{
			"User-Agent":      uaFirefoxLinux,
			"Accept-Language": "fr-FR,fr;q=0.9",
			"X-Time-Zone":     "Mars/Olympus_Mons",
		})
	}
	httpRequester, _ := httpRequestMock.GetMockedInstance()
	return httpRequester
}

// GetExpectedMetadataByTestCase metadata esperada, con las claves de AbstractInputDto.
func (m *RequestMetadataRequestMother) GetExpectedMetadataByTestCase(useCase RequestMetadataTestCaseEnum) map[string]any {
	switch useCase {
	case OK_AS_CHROME_ON_WINDOWS_IN_SPANISH:
		return m.getMetadata("Chrome", "141.0.0.0", "Windows", "Europe/Madrid", enums.LanguageCodeEnum.ES)
	case OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH:
		return m.getMetadata("Safari", "18.5", "iOS", "America/New_York", enums.LanguageCodeEnum.EN)
	case OK_AS_EDGE_NOT_CHROME_ON_WINDOWS:
		return m.getMetadata("Edge", "141.0.3537.57", "Windows", "", enums.LanguageCodeEnum.EN)
	case OK_AS_ANDROID_NOT_LINUX:
		return m.getMetadata("Chrome", "141.0.0.0", "Android", "", enums.LanguageCodeEnum.EN)
	case OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS:
		return m.getMetadata("curl", "8.10.1", "", "", enums.LanguageCodeEnum.EN)
	case OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE:
		return m.getMetadata("Firefox", "144.0", "Linux", "", enums.LanguageCodeEnum.EN)
	}
	panic("unknown test case " + string(useCase))
}

func (m *RequestMetadataRequestMother) getMetadata(
	userBrowser string,
	userBrowserVersion string,
	userOs string,
	userTimeZone string,
	userLanguageCode string,
) map[string]any {
	return map[string]any{
		"userIpAddress":      ipAddress,
		"userBrowser":        userBrowser,
		"userBrowserVersion": userBrowserVersion,
		"userOs":             userOs,
		"userTimeZone":       userTimeZone,
		"userLanguageCode":   userLanguageCode,
	}
}
