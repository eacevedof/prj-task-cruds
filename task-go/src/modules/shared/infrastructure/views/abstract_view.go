package views

import (
	"html"
	"strings"
)

const (
	cssPath = "/assets/Modules/Documentation/Css/documentation.css"
	jsPath  = "/assets/Modules/Documentation/Js/documentation.js"
)

// AbstractView esqueleto HTML de las paginas que se generan desde un .md.
type AbstractView struct{}

func (v AbstractView) GetHtmlPage(title string, htmlBody string) string {
	// htmlspecialchars(ENT_QUOTES): las mismas entidades que PHP para las comillas
	title = strings.NewReplacer("&#39;", "&#039;", "&#34;", "&quot;").Replace(html.EscapeString(title))
	return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>` + title + `</title>
<link rel="stylesheet" href="` + cssPath + `">
</head>
<body>
` + htmlBody + `
<script src="` + jsPath + `"></script>
</body>
</html>`
}

// GetReplacedContent rellena los {{placeholder}} del .md: {"{{appVersion}}": "2.0.0", ...}
func (v AbstractView) GetReplacedContent(content string, toReplace map[string]string) string {
	var oldNews []string
	for placeholder, value := range toReplace {
		oldNews = append(oldNews, placeholder, value)
	}
	return strings.NewReplacer(oldNews...).Replace(content)
}
