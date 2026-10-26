package views

import "task-go/src/modules/shared/application"

type DocumentationViewDto struct {
	appVersion       string
	appVersionUpdate string
	appBaseUrl       string
	errors           []string
}

func DocumentationViewDtoFromPrimitives(primitives map[string]any) *DocumentationViewDto {
	errors, _ := primitives["errors"].([]string)
	return &DocumentationViewDto{
		appVersion:       application.GetTrimmedString(primitives, "appVersion"),
		appVersionUpdate: application.GetTrimmedString(primitives, "appVersionUpdate"),
		appBaseUrl:       application.GetTrimmedString(primitives, "appBaseUrl"),
		errors:           errors,
	}
}

func (d *DocumentationViewDto) GetAppVersion() string {
	return d.appVersion
}

func (d *DocumentationViewDto) GetAppVersionUpdate() string {
	return d.appVersionUpdate
}

func (d *DocumentationViewDto) GetAppBaseUrl() string {
	return d.appBaseUrl
}

func (d *DocumentationViewDto) GetErrors() []string {
	return d.errors
}
