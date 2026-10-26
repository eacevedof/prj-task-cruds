package useragent

import "regexp"

// UserAgentParser navegador, version y sistema operativo a partir del User-Agent. Deteccion por patrones (sin vendor):
// suficiente para metadata/trazas, no para decidir permisos (el User-Agent lo escribe el cliente).
type UserAgentParser struct{}

type namedPattern struct {
	name    string
	pattern *regexp.Regexp
}

// El ORDEN importa: Edge y Opera tambien dicen "Chrome", Chrome tambien dice "Safari".
// nombre => patron cuyo grupo 1 es la version.
var browserPatterns = []namedPattern{
	{"Edge", regexp.MustCompile(`Edg(?:e|A|iOS)?/([\d.]+)`)},
	{"Opera", regexp.MustCompile(`(?:OPR|Opera)/([\d.]+)`)},
	{"Firefox", regexp.MustCompile(`(?:Firefox|FxiOS)/([\d.]+)`)},
	{"Chrome", regexp.MustCompile(`(?:Chrome|CriOS)/([\d.]+)`)},
	{"Safari", regexp.MustCompile(`Version/([\d.]+).*Safari/`)},
	{"Postman", regexp.MustCompile(`PostmanRuntime/([\d.]+)`)},
	{"curl", regexp.MustCompile(`^curl/([\d.]+)`)},
}

// El ORDEN importa: Android dice "Linux", iPhone/iPad dicen "Mac OS X".
var osPatterns = []namedPattern{
	{"Android", regexp.MustCompile(`Android`)},
	{"iOS", regexp.MustCompile(`iPhone|iPad|iPod`)},
	{"Windows", regexp.MustCompile(`Windows`)},
	{"Mac", regexp.MustCompile(`Macintosh|Mac OS X`)},
	{"Ubuntu", regexp.MustCompile(`Ubuntu`)},
	{"Linux", regexp.MustCompile(`Linux`)},
}

func GetInstance() *UserAgentParser {
	return &UserAgentParser{}
}

func (p *UserAgentParser) GetBrowserName(userAgent string) string {
	for _, browserPattern := range browserPatterns {
		if browserPattern.pattern.MatchString(userAgent) {
			return browserPattern.name
		}
	}
	return ""
}

func (p *UserAgentParser) GetBrowserVersion(userAgent string) string {
	for _, browserPattern := range browserPatterns {
		if matches := browserPattern.pattern.FindStringSubmatch(userAgent); matches != nil {
			return matches[1]
		}
	}
	return ""
}

func (p *UserAgentParser) GetOsName(userAgent string) string {
	for _, osPattern := range osPatterns {
		if osPattern.pattern.MatchString(userAgent) {
			return osPattern.name
		}
	}
	return ""
}
