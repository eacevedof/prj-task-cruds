class AlertEmailTypeEnum:
    """
    Tipos de email de alerta: cada uno con su etiqueta en el asunto, su cabecera y su color en el cuerpo.
    """
    ERROR = "error"
    SECURITY_THREAT = "security_threat"
    WARNING = "warning"
    NOTICE = "notice"

    SUBJECT_TAGS = {
        "error": "[alert]",
        "security_threat": "[alert]: Security Threat",
        "warning": "[warning]",
        "notice": "[notice]",
    }

    HEADINGS = {
        "error": "Error",
        "security_threat": "Security Threat Alert",
        "warning": "Warning",
        "notice": "Notice",
    }

    COLORS = {
        "error": "#D60202",
        "security_threat": "#D60202",
        "warning": "darkorange",
        "notice": "royalblue",
    }
