package markdown

import (
	"fmt"
	"regexp"
	"strings"
)

const (
	codeBlockPlaceholder  = "XZCODEBLOCKX%dXPLACEHOLDERX"
	inlineCodePlaceholder = "XZINLINECODEX%dXPLACEHOLDERX"
)

var blockTagPrefixes = []string{"<h", "<ul>", "<ol>", "<pre>", "<blockquote>", "<hr>", "<table"}

var (
	codeBlockPattern     = regexp.MustCompile("```(\\w*)\\n?([\\s\\S]*?)```")
	inlineCodePattern    = regexp.MustCompile("`([^`\\n]+)`")
	imgPattern           = regexp.MustCompile(`!\[([^\]]*)\]\(([^)]+)\)`)
	aPattern             = regexp.MustCompile(`\[([^\]]+)\]\(([^)]+)\)`)
	hPattern             = regexp.MustCompile(`(?m)^(#{1,6})\s+(.*)$`)
	tagPattern           = regexp.MustCompile(`<[^>]*>`)
	anchorInvalidPattern = regexp.MustCompile(`[^\p{L}\p{N}.]+`)
	blockquotePattern    = regexp.MustCompile(`(?m)^> (.*)$`)
	hrPattern            = regexp.MustCompile(`(?m)^(---|\*\*\*|___)\s*$`)
	ulItemPattern        = regexp.MustCompile(`^\s*[-*+]\s+(.*)$`)
	olItemPattern        = regexp.MustCompile(`^\s*\d+\.\s+(.*)$`)
	tableSeparatorPattrn = regexp.MustCompile(`^\|[\s:|-]+\|$`)
	manyNewLinesPattern  = regexp.MustCompile(`\n\n+`)
)

// textFormattingRule una regla de negrita/cursiva/tachado. Las de task-php llevan lookbehind/lookahead, que RE2 no
// tiene: notBefore/notAfter son los bytes que no pueden ir justo antes/despues del match.
type textFormattingRule struct {
	pattern     *regexp.Regexp
	replacement string
	notBefore   string
	notAfter    string
}

const wordBytes = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_"

var textFormattingRules = []textFormattingRule{
	{pattern: regexp.MustCompile(`^\*\*\*([^*\n]+)\*\*\*`), replacement: "<strong><em>$1</em></strong>"},
	{pattern: regexp.MustCompile(`^___([^_\n]+)___`), replacement: "<strong><em>$1</em></strong>"},
	{pattern: regexp.MustCompile(`^\*\*([^*\n]+)\*\*`), replacement: "<strong>$1</strong>"},
	{pattern: regexp.MustCompile(`^__([^_\n]+)__`), replacement: "<strong>$1</strong>", notBefore: "_", notAfter: "_"},
	{pattern: regexp.MustCompile(`^\*([^*\n]+)\*`), replacement: "<em>$1</em>", notBefore: "*", notAfter: "*"},
	// cursiva con _ solo entre espacios/bordes: "user_uuid" o "api_token" no son cursiva
	{pattern: regexp.MustCompile(`^_([^_\n]+)_`), replacement: "<em>$1</em>", notBefore: wordBytes, notAfter: wordBytes},
	{pattern: regexp.MustCompile(`^~~([^~\n]+)~~`), replacement: "<del>$1</del>"},
}

// Markdowner Markdown -> HTML sin vendor (port del de task-php). Cubre lo que usa la documentacion:
// titulos (con id para enlazar #anclas), negrita/cursiva/tachado, enlaces, imagenes, citas, <hr>, listas,
// tablas GFM y codigo (en bloque y en linea, escapado). El resto del texto NO se escapa: el .md es nuestro.
type Markdowner struct {
	preAndCodeBlocks []string
	inlineCodeBlocks []string
}

func GetInstance() *Markdowner {
	return &Markdowner{}
}

func (m *Markdowner) GetHtmlFromMarkdown(markdown string) string {
	m.preAndCodeBlocks = nil
	m.inlineCodeBlocks = nil

	// un checkout en Windows (autocrlf) deja CRLF y los patrones de linea ($) no casarian
	html := m.getWithCodePlaceholders(strings.ReplaceAll(markdown, "\r\n", "\n"))
	// imagenes antes que enlaces: "![alt](src)" contiene un "[alt](src)"
	html = m.getImgTags(html)
	html = m.getATags(html)
	html = m.getHTags(html)
	html = m.getTextFormattingTags(html)
	html = m.getBlockquoteTags(html)
	html = m.getHrTags(html)
	html = m.getListTags(html)
	// el codigo se restaura antes de tablas y parrafos: puede ir dentro de una celda
	html = m.getWithCodeRestored(html)
	html = m.getTableTags(html)
	return m.getPTags(html)
}

// getWithCodePlaceholders el codigo se aparta con un placeholder para que ninguna otra regla toque su contenido.
func (m *Markdowner) getWithCodePlaceholders(markdown string) string {
	markdown = codeBlockPattern.ReplaceAllStringFunc(markdown, func(match string) string {
		groups := codeBlockPattern.FindStringSubmatch(match)
		language := ""
		if groups[1] != "" {
			language = " class=\"language-" + groups[1] + "\""
		}
		m.preAndCodeBlocks = append(m.preAndCodeBlocks, "<pre><code"+language+">"+getEscapedHtml(groups[2])+"</code></pre>")
		return fmt.Sprintf(codeBlockPlaceholder, len(m.preAndCodeBlocks)-1)
	})

	return inlineCodePattern.ReplaceAllStringFunc(markdown, func(match string) string {
		groups := inlineCodePattern.FindStringSubmatch(match)
		m.inlineCodeBlocks = append(m.inlineCodeBlocks, "<code>"+getEscapedHtml(groups[1])+"</code>")
		return fmt.Sprintf(inlineCodePlaceholder, len(m.inlineCodeBlocks)-1)
	})
}

// getEscapedHtml htmlspecialchars(ENT_QUOTES): mismas entidades que PHP (&#039; y &quot;).
func getEscapedHtml(code string) string {
	return strings.NewReplacer(
		"&", "&amp;",
		"<", "&lt;",
		">", "&gt;",
		"\"", "&quot;",
		"'", "&#039;",
	).Replace(code)
}

func (m *Markdowner) getImgTags(html string) string {
	return imgPattern.ReplaceAllString(html, `<img alt="${1}" src="${2}">`)
}

func (m *Markdowner) getATags(html string) string {
	return aPattern.ReplaceAllString(html, `<a href="${2}">${1}</a>`)
}

func (m *Markdowner) getHTags(html string) string {
	return hPattern.ReplaceAllStringFunc(html, func(match string) string {
		groups := hPattern.FindStringSubmatch(match)
		level := len(groups[1])
		return fmt.Sprintf("<h%d id=\"%s\">%s</h%d>", level, m.getAnchorId(groups[2]), groups[2], level)
	})
}

// getAnchorId "2.1 Alta de usuario" -> "2-1-alta-de-usuario"; los puntos de una version se mantienen ("1.0.0").
func (m *Markdowner) getAnchorId(title string) string {
	anchorId := strings.ToLower(tagPattern.ReplaceAllString(title, ""))
	anchorId = anchorInvalidPattern.ReplaceAllString(anchorId, "-")
	return strings.Trim(anchorId, "-.")
}

func (m *Markdowner) getTextFormattingTags(html string) string {
	for _, rule := range textFormattingRules {
		html = m.getReplacedByRule(html, rule)
	}
	return html
}

// getReplacedByRule como preg_replace: prueba el patron en cada posicion y, si casa y se cumplen los bytes de
// alrededor, lo sustituye y sigue tras el match; si no, avanza un byte. Los patrones no pueden casar de dos formas
// en la misma posicion (la clase excluye el delimitador), asi que el resultado es el mismo que con lookarounds.
func (m *Markdowner) getReplacedByRule(html string, rule textFormattingRule) string {
	var replaced strings.Builder
	for position := 0; position < len(html); {
		matchIndexes := rule.pattern.FindStringSubmatchIndex(html[position:])
		if matchIndexes == nil || !m.isRuleContextOk(html, position, position+matchIndexes[1], rule) {
			replaced.WriteByte(html[position])
			position++
			continue
		}
		replaced.Write(rule.pattern.ExpandString(nil, rule.replacement, html[position:], matchIndexes))
		position += matchIndexes[1]
	}
	return replaced.String()
}

func (m *Markdowner) isRuleContextOk(html string, start int, end int, rule textFormattingRule) bool {
	if rule.notBefore != "" && start > 0 && strings.IndexByte(rule.notBefore, html[start-1]) >= 0 {
		return false
	}
	if rule.notAfter != "" && end < len(html) && strings.IndexByte(rule.notAfter, html[end]) >= 0 {
		return false
	}
	return true
}

func (m *Markdowner) getBlockquoteTags(html string) string {
	return blockquotePattern.ReplaceAllString(html, "<blockquote>${1}</blockquote>")
}

func (m *Markdowner) getHrTags(html string) string {
	return hrPattern.ReplaceAllString(html, "<hr>")
}

func (m *Markdowner) getListTags(html string) string {
	var listLines []string
	openListTag := ""
	for _, line := range strings.Split(html, "\n") {
		listTag := ""
		var groups []string
		if groups = ulItemPattern.FindStringSubmatch(line); groups != nil {
			listTag = "ul"
		} else if groups = olItemPattern.FindStringSubmatch(line); groups != nil {
			listTag = "ol"
		}

		if openListTag != "" && listTag != openListTag {
			listLines = append(listLines, "</"+openListTag+">")
			openListTag = ""
		}
		if listTag == "" {
			if strings.TrimSpace(line) != "" {
				listLines = append(listLines, line)
			}
			continue
		}
		if openListTag == "" {
			listLines = append(listLines, "<"+listTag+">")
			openListTag = listTag
		}
		listLines = append(listLines, "<li>"+groups[1]+"</li>")
	}
	if openListTag != "" {
		listLines = append(listLines, "</"+openListTag+">")
	}
	return strings.Join(listLines, "\n")
}

func (m *Markdowner) getWithCodeRestored(html string) string {
	for index, inlineCodeBlock := range m.inlineCodeBlocks {
		html = strings.ReplaceAll(html, fmt.Sprintf(inlineCodePlaceholder, index), inlineCodeBlock)
	}
	for index, preAndCodeBlock := range m.preAndCodeBlocks {
		html = strings.ReplaceAll(html, fmt.Sprintf(codeBlockPlaceholder, index), preAndCodeBlock)
	}
	return html
}

// getTableTags tabla = fila "| a | b |" seguida de una separadora "|---|:--:|" (que fija la alineacion).
func (m *Markdowner) getTableTags(html string) string {
	lines := strings.Split(html, "\n")
	var tableLines []string
	for i := 0; i < len(lines); {
		line := strings.TrimSpace(lines[i])
		isTableStart := strings.HasPrefix(line, "|") &&
			i+1 < len(lines) &&
			tableSeparatorPattrn.MatchString(strings.TrimSpace(lines[i+1]))
		if !isTableStart {
			tableLines = append(tableLines, lines[i])
			i++
			continue
		}

		alignments := m.getTableAlignments(strings.TrimSpace(lines[i+1]))
		tableHtml := "<table class=\"md-table\">\n<thead>\n<tr>" +
			m.getTableCells(line, "th", alignments) +
			"</tr>\n</thead>\n<tbody>"
		i += 2
		for i < len(lines) && strings.HasPrefix(strings.TrimSpace(lines[i]), "|") {
			tableHtml += "\n<tr>" + m.getTableCells(strings.TrimSpace(lines[i]), "td", alignments) + "</tr>"
			i++
		}
		tableLines = append(tableLines, tableHtml+"\n</tbody>\n</table>")
	}
	return strings.Join(tableLines, "\n")
}

func (m *Markdowner) getTableCells(row string, cellTag string, alignments []string) string {
	var cellsHtml strings.Builder
	for index, cell := range m.getTableRowCells(row) {
		style := ""
		if index < len(alignments) && alignments[index] != "" {
			style = " style=\"text-align:" + alignments[index] + "\""
		}
		cellsHtml.WriteString("<" + cellTag + style + ">" + strings.TrimSpace(cell) + "</" + cellTag + ">")
	}
	return cellsHtml.String()
}

func (m *Markdowner) getTableRowCells(row string) []string {
	return strings.Split(strings.Trim(strings.TrimSpace(row), "|"), "|")
}

func (m *Markdowner) getTableAlignments(separatorRow string) []string {
	var alignments []string
	for _, cell := range m.getTableRowCells(separatorRow) {
		cell = strings.TrimSpace(cell)
		isLeft := strings.HasPrefix(cell, ":")
		isRight := strings.HasSuffix(cell, ":")
		switch {
		case isLeft && isRight:
			alignments = append(alignments, "center")
		case isRight:
			alignments = append(alignments, "right")
		case isLeft:
			alignments = append(alignments, "left")
		default:
			alignments = append(alignments, "")
		}
	}
	return alignments
}

func (m *Markdowner) getPTags(html string) string {
	var paragraphsHtml []string
	for _, paragraph := range strings.Split(manyNewLinesPattern.ReplaceAllString(html, "\n\n"), "\n\n") {
		if strings.TrimSpace(paragraph) == "" {
			continue
		}
		paragraphsHtml = append(paragraphsHtml, m.getParagraphHtml(strings.TrimSpace(paragraph)))
	}
	return strings.Join(paragraphsHtml, "\n")
}

func (m *Markdowner) getParagraphHtml(paragraph string) string {
	for _, blockTagPrefix := range blockTagPrefixes {
		if strings.HasPrefix(paragraph, blockTagPrefix) {
			return paragraph
		}
	}
	if strings.Contains(paragraph, "<table") || strings.Contains(paragraph, "<pre><code") {
		return paragraph
	}
	return "<p>" + strings.ReplaceAll(paragraph, "\n", "<br>") + "</p>"
}
