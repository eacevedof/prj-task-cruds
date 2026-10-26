package sendalertemail

import (
	"errors"
	"html"

	"task-go/src/modules/mailer/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	"task-go/src/modules/shared/infrastructure/components/mailer"
	"task-go/src/modules/shared/infrastructure/components/server"
	"task-go/src/modules/shared/infrastructure/repositories/configuration"
)

// SendAlertEmailService email de alerta al equipo (error, amenaza de seguridad, warning, notice) a APP_EMAIL_TO.
// Sin APP_EMAIL_TO no envia (alertas desactivadas). No captura: si el envio falla escala, y quien lo usa
// (MailTrait) decide que hacer para no tapar el error original.
type SendAlertEmailService struct {
	dateTimer                      *datetimer.DateTimer
	funcMailer                     *mailer.FuncMailer
	serverIdentifier               *server.ServerIdentifier
	environmentReaderRawRepository *configuration.EnvironmentReaderRawRepository

	sendAlertEmailDto *SendAlertEmailDto
}

func SendAlertEmailServiceGetInstance() *SendAlertEmailService {
	return &SendAlertEmailService{
		dateTimer:                      datetimer.GetInstance(),
		funcMailer:                     mailer.GetInstance(),
		serverIdentifier:               server.GetInstance(),
		environmentReaderRawRepository: configuration.GetInstance(),
	}
}

func (s *SendAlertEmailService) Invoke(sendAlertEmailDto *SendAlertEmailDto) (*SendAlertEmailResultDto, error) {
	s.sendAlertEmailDto = sendAlertEmailDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	emailTo := s.environmentReaderRawRepository.GetEmailTo()
	if emailTo == "" {
		return SendAlertEmailResultDtoFromPrimitives(map[string]any{"isSent": false, "emailTo": ""}), nil
	}

	environment := s.environmentReaderRawRepository.GetEnvironment()
	appName := s.environmentReaderRawRepository.GetAppName()
	emailFrom := s.environmentReaderRawRepository.GetEmailFrom()
	if emailFrom == "" {
		emailFrom = emailTo
	}
	s.funcMailer.SetFrom(emailFrom)
	s.funcMailer.SetTitleFrom(appName + " (" + environment + ")")
	s.funcMailer.AddTo(emailTo)
	s.funcMailer.SetSubject(s.getSubject(environment))
	s.funcMailer.SetContent(s.getHtmlBody())
	if err := s.funcMailer.Send(); err != nil {
		return nil, err
	}

	return SendAlertEmailResultDtoFromPrimitives(map[string]any{"isSent": true, "emailTo": emailTo}), nil
}

// failIfWrongInput la alerta la genera el codigo (MailTrait), no un usuario: un dato mal formado es un fallo de
// programacion.
func (s *SendAlertEmailService) failIfWrongInput() error {
	alertType := s.sendAlertEmailDto.GetAlertType()
	if _, isKnown := enums.AlertEmailTypeEnum.SUBJECT_TAGS[alertType]; !isKnown {
		return errors.New("SendAlertEmail: unknown alert type \"" + alertType + "\"")
	}
	if s.sendAlertEmailDto.GetSubject() == "" {
		return errors.New("SendAlertEmail: subject is required")
	}
	return nil
}

func (s *SendAlertEmailService) getSubject(environment string) string {
	subjectTag := enums.AlertEmailTypeEnum.SUBJECT_TAGS[s.sendAlertEmailDto.GetAlertType()]
	serverInfo := s.serverIdentifier.GetServerName() + " (" + s.serverIdentifier.GetServerIp() + ")"
	return subjectTag + " (" + environment + ") " + serverInfo + " - " + s.sendAlertEmailDto.GetSubject()
}

func (s *SendAlertEmailService) getHtmlBody() string {
	alertType := s.sendAlertEmailDto.GetAlertType()
	heading := enums.AlertEmailTypeEnum.HEADINGS[alertType]
	color := enums.AlertEmailTypeEnum.COLORS[alertType]
	now := s.dateTimer.GetNowDtIntoTargetTz()
	baseUrl := html.EscapeString(s.environmentReaderRawRepository.GetBaseUrl())
	subject := html.EscapeString(s.sendAlertEmailDto.GetSubject())
	details := html.EscapeString(s.sendAlertEmailDto.GetDetails())

	return `
            <h3 style="color:` + color + `;">[` + now + `] ` + heading + `: ` + subject + `</h3>
            <p><a href="` + baseUrl + `" target="_blank">` + baseUrl + `</a></p>
            <pre>` + details + `</pre>
        `
}
