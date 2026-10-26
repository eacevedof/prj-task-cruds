package sendalertemail

import "task-go/src/modules/shared/application"

type SendAlertEmailResultDto struct {
	// false sin error: APP_EMAIL_TO vacio = alertas por email desactivadas (p.ej. en los tests)
	isSent  bool
	emailTo string
}

func SendAlertEmailResultDtoFromPrimitives(primitives map[string]any) *SendAlertEmailResultDto {
	isSent, _ := primitives["isSent"].(bool)
	return &SendAlertEmailResultDto{
		isSent:  isSent,
		emailTo: application.GetTrimmedString(primitives, "emailTo"),
	}
}

func (d *SendAlertEmailResultDto) IsSent() bool {
	return d.isSent
}

func (d *SendAlertEmailResultDto) GetEmailTo() string {
	return d.emailTo
}
