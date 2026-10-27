package modules.mailer.application.sendalertemail;

import java.util.Map;

import static modules.shared.application.Primitives.getTrimmedString;

public final class SendAlertEmailDto {

    private final String alertType;
    private final String subject;
    private final String details;

    /**
     * primitives: alertType (AlertEmailTypeEnum), subject, details (texto plano: el servicio lo escapa)
     */
    private SendAlertEmailDto(Map<String, Object> primitives) {
        this.alertType = getTrimmedString(primitives.get("alertType"));
        this.subject = getTrimmedString(primitives.get("subject"));
        this.details = getTrimmedString(primitives.get("details"));
    }

    public static SendAlertEmailDto fromPrimitives(Map<String, Object> primitives) {
        return new SendAlertEmailDto(primitives);
    }

    public String getAlertType() {
        return alertType;
    }

    public String getSubject() {
        return subject;
    }

    public String getDetails() {
        return details;
    }
}
