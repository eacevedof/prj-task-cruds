package sendalertemail

import "task-go/src/modules/shared/application"

type SendAlertEmailDto struct {
	alertType string
	subject   string
	details   string
}

// SendAlertEmailDtoFromPrimitives alertType (AlertEmailTypeEnum), subject, details (texto plano: el servicio lo escapa)
func SendAlertEmailDtoFromPrimitives(primitives map[string]any) *SendAlertEmailDto {
	return &SendAlertEmailDto{
		alertType: application.GetTrimmedString(primitives, "alertType"),
		subject:   application.GetTrimmedString(primitives, "subject"),
		details:   application.GetTrimmedString(primitives, "details"),
	}
}

func (d *SendAlertEmailDto) GetAlertType() string {
	return d.alertType
}

func (d *SendAlertEmailDto) GetSubject() string {
	return d.subject
}

func (d *SendAlertEmailDto) GetDetails() string {
	return d.details
}
