/**
 * Tipos de email de alerta: cada uno con su etiqueta en el asunto, su cabecera y su color en el cuerpo.
 */
export const AlertEmailTypeEnum = Object.freeze({
    ERROR: "error",
    SECURITY_THREAT: "security_threat",
    WARNING: "warning",
    NOTICE: "notice",

    SUBJECT_TAGS: Object.freeze<Record<string, string>>({
        error: "[alert]",
        security_threat: "[alert]: Security Threat",
        warning: "[warning]",
        notice: "[notice]",
    }),

    HEADINGS: Object.freeze<Record<string, string>>({
        error: "Error",
        security_threat: "Security Threat Alert",
        warning: "Warning",
        notice: "Notice",
    }),

    COLORS: Object.freeze<Record<string, string>>({
        error: "#D60202",
        security_threat: "#D60202",
        warning: "darkorange",
        notice: "royalblue",
    }),
});
