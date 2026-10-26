package mailer

import (
	"bytes"
	"crypto/md5"
	"encoding/base64"
	"encoding/hex"
	"errors"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
	"time"

	"task-go/src/modules/shared/infrastructure/repositories/configuration"
)

const defaultSendmailPath = "/usr/sbin/sendmail -t -i"

// FuncMailer el mail() de PHP: html y adjuntos opcionales, entregado al comando de APP_SENDMAIL_PATH (el
// sendmail_path del php.ini). En local es el sendmail de busybox apuntando a Mailpit (docker/app/Dockerfile).
// Componente: no captura ni loguea, si no se puede enviar devuelve el error.
type FuncMailer struct {
	AbstractMailer
	boundary string
}

func GetInstance() *FuncMailer {
	return &FuncMailer{}
}

func (m *FuncMailer) Send() error {
	if m.emailFrom == "" || len(m.emailsTo) == 0 {
		return errors.New("FuncMailer: email from and at least one email to are required")
	}

	m.loadBoundary()
	m.loadHeaderFrom()
	m.loadHeaderMime()
	m.loadHeaderCc()
	m.loadHeaderBcc()

	content := m.getMultipart() + "\n"
	content += m.content + "\n"
	for _, attachment := range m.attachments {
		content += m.getAttachment(attachment)
	}

	return m.sendmail(strings.Join(m.emailsTo, ", "), m.subject, content, strings.Join(m.headers, "\n"))
}

// sendmail lo que hace mail(): To y Subject delante de las cabeceras, linea en blanco y el cuerpo, por stdin.
func (m *FuncMailer) sendmail(to string, subject string, content string, headers string) error {
	sendmailPath := configuration.GetInstance().GetSendmailPath()
	if sendmailPath == "" {
		sendmailPath = defaultSendmailPath
	}
	sendmailCommand := strings.Fields(sendmailPath)

	message := "To: " + to + "\nSubject: " + subject + "\n" + headers + "\n\n" + content
	command := exec.Command(sendmailCommand[0], sendmailCommand[1:]...)
	command.Stdin = strings.NewReader(message)
	var stderr bytes.Buffer
	command.Stderr = &stderr
	if err := command.Run(); err != nil {
		return fmt.Errorf("FuncMailer: mail() failed. %w %s", err, strings.TrimSpace(stderr.String()))
	}
	return nil
}

func (m *FuncMailer) loadBoundary() {
	if len(m.attachments) > 0 {
		uniqueId := md5.Sum([]byte(fmt.Sprint(time.Now().UnixNano())))
		m.boundary = hex.EncodeToString(uniqueId[:])
	}
}

func (m *FuncMailer) loadHeaderFrom() {
	// nombre entre comillas: sin ellas, lo que va entre parentesis ("app (local)") es un comentario y se pierde
	titleFrom := strings.ReplaceAll(m.titleFrom, "\"", "")
	m.headers = append(m.headers,
		"From: \""+titleFrom+"\" <"+m.emailFrom+">",
		"Return-Path: <"+m.emailFrom+">",
		"X-Sender: "+m.emailFrom,
	)
}

func (m *FuncMailer) loadHeaderMime() {
	headers := []string{
		"MIME-Version: 1.0",
		"Content-Type: text/html; charset=\"UTF-8\"",
		"Content-Transfer-Encoding: 8bit",
	}
	if m.boundary != "" {
		headers = []string{
			"MIME-Version: 1.0",
			"Content-Type: multipart/mixed; boundary=\"" + m.boundary + "\"",
			"Content-Transfer-Encoding: 7bit",
			"This is a MIME encoded message.",
		}
	}
	m.headers = append(m.headers, headers...)
}

func (m *FuncMailer) loadHeaderCc() {
	if len(m.emailsCc) > 0 {
		m.headers = append(m.headers, "cc: "+strings.Join(m.emailsCc, ", "))
	}
}

func (m *FuncMailer) loadHeaderBcc() {
	if len(m.emailsBcc) > 0 {
		m.headers = append(m.headers, "bcc: "+strings.Join(m.emailsBcc, ", "))
	}
}

func (m *FuncMailer) getMultipart() string {
	if m.boundary == "" {
		return ""
	}
	return strings.Join([]string{
		"--" + m.boundary,
		"Content-Type: text/html; charset=UTF-8",
		"Content-Transfer-Encoding: 8bit",
	}, "\n")
}

func (m *FuncMailer) getAttachment(attachment MailAttachment) string {
	content, err := os.ReadFile(attachment.Path)
	if err != nil || len(content) == 0 {
		return ""
	}

	mime := attachment.Mime
	if mime == "" {
		mime = "application/octet-stream"
	}
	alias := attachment.FileName
	if alias == "" {
		alias = filepath.Base(attachment.Path)
	}

	return strings.Join([]string{
		"",
		"--" + m.boundary,
		"Content-Type: " + mime + "; name=\"" + alias + "\"",
		"Content-Transfer-Encoding: base64",
		"Content-Disposition: attachment; ",
		chunkSplit(base64.StdEncoding.EncodeToString(content), 76),
		"--" + m.boundary + "--",
		"",
	}, "\n")
}

// chunkSplit chunk_split(): trozos de chunkLength con "\r\n" detras de cada uno.
func chunkSplit(text string, chunkLength int) string {
	var chunks strings.Builder
	for start := 0; start < len(text); start += chunkLength {
		end := min(start+chunkLength, len(text))
		chunks.WriteString(text[start:end] + "\r\n")
	}
	return chunks.String()
}
