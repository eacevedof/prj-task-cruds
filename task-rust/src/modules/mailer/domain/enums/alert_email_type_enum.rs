/// tipos de email de alerta: cada uno con su etiqueta en el asunto, su cabecera y su color en el cuerpo.
pub struct AlertEmailTypeEnum;

impl AlertEmailTypeEnum {
    pub const ERROR: &'static str = "error";
    pub const SECURITY_THREAT: &'static str = "security_threat";
    pub const WARNING: &'static str = "warning";
    pub const NOTICE: &'static str = "notice";

    pub const SUBJECT_TAGS: [(&'static str, &'static str); 4] = [
        ("error", "[alert]"),
        ("security_threat", "[alert]: Security Threat"),
        ("warning", "[warning]"),
        ("notice", "[notice]"),
    ];
    pub const HEADINGS: [(&'static str, &'static str); 4] = [
        ("error", "Error"),
        ("security_threat", "Security Threat Alert"),
        ("warning", "Warning"),
        ("notice", "Notice"),
    ];
    pub const COLORS: [(&'static str, &'static str); 4] =
        [("error", "#D60202"), ("security_threat", "#D60202"), ("warning", "darkorange"), ("notice", "royalblue")];

    /// el $MAPA[$alertType] de PHP: None si el tipo no esta en el mapa.
    pub fn get_value(map: &[(&'static str, &'static str)], alert_type: &str) -> Option<&'static str> {
        map.iter().find(|(key, _)| *key == alert_type).map(|(_, value)| *value)
    }
}
