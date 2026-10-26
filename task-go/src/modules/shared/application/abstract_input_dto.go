package application

import "task-go/src/modules/shared/domain/enums"

// AbstractInputDto metadata de la peticion que viaja con TODO dto de entrada hasta la logica de negocio (ip,
// navegador, so, zona horaria, idioma). La rellena el controller (HttpRequester.GetMetadata), que es quien lee el
// transporte: el servicio solo ve su dto. Desde CLI o un test llega vacia y el idioma cae en EN.
// El dto hijo la embebe con NewAbstractInputDto(primitives).
type AbstractInputDto struct {
	userIpAddress      string
	userBrowser        string
	userBrowserVersion string
	userOs             string
	userTimeZone       string
	userLanguageCode   string
}

func NewAbstractInputDto(primitives map[string]any) AbstractInputDto {
	userLanguageCode := GetTrimmedString(primitives, "userLanguageCode")
	if userLanguageCode == "" {
		userLanguageCode = enums.LanguageCodeEnum.EN
	}
	return AbstractInputDto{
		userIpAddress:      GetTrimmedString(primitives, "userIpAddress"),
		userBrowser:        GetTrimmedString(primitives, "userBrowser"),
		userBrowserVersion: GetTrimmedString(primitives, "userBrowserVersion"),
		userOs:             GetTrimmedString(primitives, "userOs"),
		userTimeZone:       GetTrimmedString(primitives, "userTimeZone"),
		userLanguageCode:   userLanguageCode,
	}
}

func (d AbstractInputDto) GetUserIpAddress() string {
	return d.userIpAddress
}

func (d AbstractInputDto) GetUserBrowser() string {
	return d.userBrowser
}

func (d AbstractInputDto) GetUserBrowserVersion() string {
	return d.userBrowserVersion
}

func (d AbstractInputDto) GetUserOs() string {
	return d.userOs
}

func (d AbstractInputDto) GetUserTimeZone() string {
	return d.userTimeZone
}

func (d AbstractInputDto) GetUserLanguageCode() string {
	return d.userLanguageCode
}
