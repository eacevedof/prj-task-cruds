package modules.mailer.application.sendalertemail;

import modules.mailer.domain.enums.AlertEmailTypeEnum;
import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.date.DateTimer;
import modules.shared.infrastructure.components.html.HtmlEscaper;
import modules.shared.infrastructure.components.mailer.FuncMailer;
import modules.shared.infrastructure.components.server.ServerIdentifier;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

/**
 * Email de alerta al equipo (error, amenaza de seguridad, warning, notice) a APP_EMAIL_TO.
 * Sin APP_EMAIL_TO no envia (alertas desactivadas). No captura: si el envio falla escala, y quien lo usa
 * (MailTrait) decide que hacer para no tapar el error original.
 */
public final class SendAlertEmailService {

    private final DateTimer dateTimer;
    private final FuncMailer funcMailer;
    private final HtmlEscaper htmlEscaper;
    private final ServerIdentifier serverIdentifier;
    private final EnvironmentReaderRawRepository environmentReaderRawRepository;

    private SendAlertEmailDto sendAlertEmailDto;

    private SendAlertEmailService() {
        this.dateTimer = DateTimer.getInstance();
        this.funcMailer = FuncMailer.getInstance();
        this.htmlEscaper = HtmlEscaper.getInstance();
        this.serverIdentifier = ServerIdentifier.getInstance();
        this.environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
    }

    public static SendAlertEmailService getInstance() {
        return new SendAlertEmailService();
    }

    /**
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     */
    public SendAlertEmailResultDto invoke(SendAlertEmailDto sendAlertEmailDto) {
        this.sendAlertEmailDto = sendAlertEmailDto;
        failIfWrongInput();

        String emailTo = environmentReaderRawRepository.getEmailTo();
        if (emailTo.isEmpty()) {
            return SendAlertEmailResultDto.fromPrimitives(Primitives.of("isSent", false, "emailTo", ""));
        }

        String environment = environmentReaderRawRepository.getEnvironment();
        String appName = environmentReaderRawRepository.getAppName();
        String emailFrom = environmentReaderRawRepository.getEmailFrom();
        funcMailer
            .setFrom(emailFrom.isEmpty() ? emailTo : emailFrom)
            .setTitleFrom(appName + " (" + environment + ")")
            .addTo(emailTo)
            .setSubject(getSubject(environment))
            .setContent(getHtmlBody())
            .send();

        return SendAlertEmailResultDto.fromPrimitives(Primitives.of("isSent", true, "emailTo", emailTo));
    }

    /**
     * La alerta la genera el codigo (MailTrait), no un usuario: un dato mal formado es un fallo de programacion.
     * @throws IllegalArgumentException
     */
    private void failIfWrongInput() {
        String alertType = sendAlertEmailDto.getAlertType();
        if (!AlertEmailTypeEnum.SUBJECT_TAGS.containsKey(alertType)) {
            throw new IllegalArgumentException("SendAlertEmail: unknown alert type \"" + alertType + "\"");
        }
        if (sendAlertEmailDto.getSubject().isEmpty()) {
            throw new IllegalArgumentException("SendAlertEmail: subject is required");
        }
    }

    private String getSubject(String environment) {
        String subjectTag = AlertEmailTypeEnum.SUBJECT_TAGS.get(sendAlertEmailDto.getAlertType());
        String serverInfo = serverIdentifier.getServerName() + " (" + serverIdentifier.getServerIp() + ")";
        return subjectTag + " (" + environment + ") " + serverInfo + " - " + sendAlertEmailDto.getSubject();
    }

    private String getHtmlBody() {
        String alertType = sendAlertEmailDto.getAlertType();
        String heading = AlertEmailTypeEnum.HEADINGS.get(alertType);
        String color = AlertEmailTypeEnum.COLORS.get(alertType);
        String now = dateTimer.getNowDtIntoTargetTz();
        String baseUrl = htmlEscaper.getEscapedHtml(environmentReaderRawRepository.getBaseUrl());
        String subject = htmlEscaper.getEscapedHtml(sendAlertEmailDto.getSubject());
        String details = htmlEscaper.getEscapedHtml(sendAlertEmailDto.getDetails());

        return """
            <h3 style="color:%s;">[%s] %s: %s</h3>
            <p><a href="%s" target="_blank">%s</a></p>
            <pre>%s</pre>
            """.formatted(color, now, heading, subject, baseUrl, baseUrl, details);
    }
}
