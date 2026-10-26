package htmler

import (
	"strings"

	"task-webassembly/src/modules/shared/application"
)

var htmlEntitiesReplacer = strings.NewReplacer(
	"&", "&amp;",
	"<", "&lt;",
	">", "&gt;",
	"\"", "&quot;",
	"'", "&#39;",
)

// Htmler todo texto que viene de la API o del usuario se escapa antes de entrar en innerHTML (XSS).
type Htmler struct{}

func GetInstance() *Htmler {
	return &Htmler{}
}

func (h *Htmler) GetEscaped(text any) string {
	return htmlEntitiesReplacer.Replace(application.ToString(text))
}
