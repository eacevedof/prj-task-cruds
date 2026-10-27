package modules.mailer.domain.enums;

import java.util.Map;

/**
 * Tipos de email de alerta: cada uno con su etiqueta en el asunto, su cabecera y su color en el cuerpo.
 */
public final class AlertEmailTypeEnum {

    public static final String ERROR = "error";
    public static final String SECURITY_THREAT = "security_threat";
    public static final String WARNING = "warning";
    public static final String NOTICE = "notice";

    public static final Map<String, String> SUBJECT_TAGS = Map.of(
        ERROR, "[alert]",
        SECURITY_THREAT, "[alert]: Security Threat",
        WARNING, "[warning]",
        NOTICE, "[notice]"
    );

    public static final Map<String, String> HEADINGS = Map.of(
        ERROR, "Error",
        SECURITY_THREAT, "Security Threat Alert",
        WARNING, "Warning",
        NOTICE, "Notice"
    );

    public static final Map<String, String> COLORS = Map.of(
        ERROR, "#D60202",
        SECURITY_THREAT, "#D60202",
        WARNING, "darkorange",
        NOTICE, "royalblue"
    );

    private AlertEmailTypeEnum() {
    }
}
