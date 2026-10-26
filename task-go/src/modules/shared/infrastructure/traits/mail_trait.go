package traits

import (
	"fmt"
	"runtime/debug"
	"strings"

	"task-go/src/modules/mailer/application/sendalertemail"
	"task-go/src/modules/mailer/domain/enums"
)

// MailTrait emails de alerta desde un controller o un command. Se llama desde su rama de error: por eso aqui SI se
// captura, un fallo al enviar se loguea y nunca tapa el error original. Necesita un LogTrait para ese log.
type MailTrait struct {
	logTrait LogTrait
}

func NewMailTrait(logTrait LogTrait) MailTrait {
	return MailTrait{logTrait: logTrait}
}

func (t MailTrait) SendMailOnThrowable(err error, title string) {
	t.sendAlertEmail(enums.AlertEmailTypeEnum.ERROR, title, strings.Join([]string{
		fmt.Sprintf("%T: %s", err, err.Error()),
		"",
		"Trace:",
		string(debug.Stack()),
	}, "\n"))
}

func (t MailTrait) SendEmailOnSecurityThreat(message string, subject string) {
	t.sendAlertEmail(enums.AlertEmailTypeEnum.SECURITY_THREAT, subject, message)
}

func (t MailTrait) SendEmailWarning(message string, subject string) {
	t.sendAlertEmail(enums.AlertEmailTypeEnum.WARNING, subject, message)
}

func (t MailTrait) SendEmailNotice(message string, subject string) {
	t.sendAlertEmail(enums.AlertEmailTypeEnum.NOTICE, subject, message)
}

func (t MailTrait) sendAlertEmail(alertType string, subject string, details string) {
	_, err := sendalertemail.SendAlertEmailServiceGetInstance().Invoke(
		sendalertemail.SendAlertEmailDtoFromPrimitives(map[string]any{
			"alertType": alertType,
			"subject":   subject,
			"details":   details,
		}),
	)
	if err != nil {
		t.logTrait.LogException(err, "MailTrait.sendAlertEmail")
	}
}
