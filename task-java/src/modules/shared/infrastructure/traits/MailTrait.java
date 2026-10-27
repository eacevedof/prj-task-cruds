package modules.shared.infrastructure.traits;

import modules.mailer.application.sendalertemail.SendAlertEmailDto;
import modules.mailer.application.sendalertemail.SendAlertEmailService;
import modules.mailer.domain.enums.AlertEmailTypeEnum;
import modules.shared.application.Primitives;
import modules.shared.infrastructure.exceptions.InterfaceException;

/**
 * Emails de alerta desde un controller o un command (de app-plataformabase / prj-mypromos, MailTrait).
 * Se llama desde su catch: por eso aqui SI se captura, un fallo al enviar se loguea y nunca tapa el error
 * original. Lleva su propio LogTrait (logException).
 */
public final class MailTrait {

    private final LogTrait logTrait;

    private MailTrait() {
        this.logTrait = LogTrait.getInstance();
    }

    public static MailTrait getInstance() {
        return new MailTrait();
    }

    public void sendMailOnThrowable(Throwable throwable, String title) {
        sendAlertEmail(AlertEmailTypeEnum.ERROR, title, String.join("\n",
            throwable.getClass().getSimpleName() + ": " + throwable.getMessage(),
            "File: " + InterfaceException.getThrowableFile(throwable) + " (line " + InterfaceException.getThrowableLine(throwable) + ")",
            "",
            "Trace:",
            InterfaceException.getThrowableTraceAsString(throwable)
        ));
    }

    public void sendEmailOnSecurityThreat(String message, String subject) {
        sendAlertEmail(AlertEmailTypeEnum.SECURITY_THREAT, subject, message);
    }

    public void sendEmailWarning(String message, String subject) {
        sendAlertEmail(AlertEmailTypeEnum.WARNING, subject, message);
    }

    public void sendEmailNotice(String message, String subject) {
        sendAlertEmail(AlertEmailTypeEnum.NOTICE, subject, message);
    }

    private void sendAlertEmail(String alertType, String subject, String details) {
        try {
            SendAlertEmailService.getInstance().invoke(
                SendAlertEmailDto.fromPrimitives(Primitives.of(
                    "alertType", alertType,
                    "subject", subject,
                    "details", details
                ))
            );
        }
        catch (Exception e) {
            logTrait.logException(e, "MailTrait::sendAlertEmail");
        }
    }
}
