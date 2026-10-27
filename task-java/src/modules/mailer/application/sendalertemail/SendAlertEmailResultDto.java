package modules.mailer.application.sendalertemail;

import java.util.Map;

import static modules.shared.application.Primitives.getTrimmedString;

public final class SendAlertEmailResultDto {

    // false sin error: APP_EMAIL_TO vacio = alertas por email desactivadas (p.ej. en los tests)
    private final boolean isSent;
    private final String emailTo;

    private SendAlertEmailResultDto(Map<String, Object> primitives) {
        this.isSent = Boolean.TRUE.equals(primitives.get("isSent"));
        this.emailTo = getTrimmedString(primitives.get("emailTo"));
    }

    public static SendAlertEmailResultDto fromPrimitives(Map<String, Object> primitives) {
        return new SendAlertEmailResultDto(primitives);
    }

    public boolean isSent() {
        return isSent;
    }

    public String getEmailTo() {
        return emailTo;
    }
}
