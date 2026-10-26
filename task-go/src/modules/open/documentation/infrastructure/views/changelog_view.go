package views

import (
	_ "embed"

	"task-go/src/modules/shared/infrastructure/components/markdown"
	sharedviews "task-go/src/modules/shared/infrastructure/views"
)

const changelogTitle = "Task Go CRUD API - changelog"

//go:embed changelog.md
var changelogMarkdown string

// ChangelogView publica changelog.md (una seccion por version; el titulo de la doc enlaza a #<version>).
type ChangelogView struct {
	sharedviews.AbstractView
	markdowner *markdown.Markdowner
}

func ChangelogViewGetInstance() *ChangelogView {
	return &ChangelogView{markdowner: markdown.GetInstance()}
}

func (v *ChangelogView) GetHtml(documentationViewDto *DocumentationViewDto) string {
	changelog := v.GetReplacedContent(changelogMarkdown, map[string]string{
		"{{appBaseUrl}}": documentationViewDto.GetAppBaseUrl(),
	})
	return v.GetHtmlPage(changelogTitle, v.markdowner.GetHtmlFromMarkdown(changelog))
}
