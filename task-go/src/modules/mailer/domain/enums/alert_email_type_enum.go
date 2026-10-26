package enums

// AlertEmailTypeEnum tipos de email de alerta: cada uno con su etiqueta en el asunto, su cabecera y su color en el
// cuerpo.
var AlertEmailTypeEnum = struct {
	ERROR           string
	SECURITY_THREAT string
	WARNING         string
	NOTICE          string

	SUBJECT_TAGS map[string]string
	HEADINGS     map[string]string
	COLORS       map[string]string
}{
	ERROR:           "error",
	SECURITY_THREAT: "security_threat",
	WARNING:         "warning",
	NOTICE:          "notice",

	SUBJECT_TAGS: map[string]string{
		"error":           "[alert]",
		"security_threat": "[alert]: Security Threat",
		"warning":         "[warning]",
		"notice":          "[notice]",
	},
	HEADINGS: map[string]string{
		"error":           "Error",
		"security_threat": "Security Threat Alert",
		"warning":         "Warning",
		"notice":          "Notice",
	},
	COLORS: map[string]string{
		"error":           "#D60202",
		"security_threat": "#D60202",
		"warning":         "darkorange",
		"notice":          "royalblue",
	},
}
