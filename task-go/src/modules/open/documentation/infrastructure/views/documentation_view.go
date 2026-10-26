package views

import (
	_ "embed"

	"task-go/src/modules/shared/infrastructure/components/markdown"
	sharedviews "task-go/src/modules/shared/infrastructure/views"
)

const documentationTitle = "Task Go CRUD API"

//go:embed documentation.md
var documentationMarkdown string

// DocumentationView publica documentation.md: TODA la documentacion de la API vive en ese .md (aqui no hay texto).
// El .md va dentro del binario (go:embed).
type DocumentationView struct {
	sharedviews.AbstractView
	markdowner *markdown.Markdowner
}

func DocumentationViewGetInstance() *DocumentationView {
	return &DocumentationView{markdowner: markdown.GetInstance()}
}

func (v *DocumentationView) GetHtml(documentationViewDto *DocumentationViewDto) string {
	documentation := v.GetReplacedContent(documentationMarkdown, map[string]string{
		"{{appVersion}}":       documentationViewDto.GetAppVersion(),
		"{{appVersionUpdate}}": documentationViewDto.GetAppVersionUpdate(),
		"{{appBaseUrl}}":       documentationViewDto.GetAppBaseUrl(),
	})
	return v.GetHtmlPage(documentationTitle, v.markdowner.GetHtmlFromMarkdown(documentation))
}
