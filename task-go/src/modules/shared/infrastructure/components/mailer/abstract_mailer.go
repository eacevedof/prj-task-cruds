package mailer

import "strings"

// InterfaceMailer lo que tiene que hacer todo mailer.
type InterfaceMailer interface {
	Send() error
}

// MailAttachment fichero adjunto: path obligatorio; Mime y FileName opcionales.
type MailAttachment struct {
	Path     string
	Mime     string
	FileName string
}

// AbstractMailer datos comunes de un email. El envio lo hace cada mailer.
// Sin pila de errores: si no se puede enviar, Send() devuelve el error.
type AbstractMailer struct {
	headers     []string
	emailFrom   string
	titleFrom   string
	emailsTo    []string
	emailsCc    []string
	emailsBcc   []string
	attachments []MailAttachment

	subject string
	content string
}

func (m *AbstractMailer) SetSubject(subject string) {
	m.subject = subject
}

func (m *AbstractMailer) SetFrom(email string) {
	m.emailFrom = email
}

func (m *AbstractMailer) SetTitleFrom(title string) {
	m.titleFrom = title
}

func (m *AbstractMailer) AddTo(email string) {
	m.emailsTo = append(m.emailsTo, email)
}

func (m *AbstractMailer) AddCc(email string) {
	m.emailsCc = append(m.emailsCc, email)
}

func (m *AbstractMailer) AddBcc(email string) {
	m.emailsBcc = append(m.emailsBcc, email)
}

// SetContent html; varias lineas se unen con saltos de linea.
func (m *AbstractMailer) SetContent(contentLines ...string) {
	m.content = strings.Join(contentLines, "\n")
}

func (m *AbstractMailer) AddAttachment(attachment MailAttachment) {
	m.attachments = append(m.attachments, attachment)
}
