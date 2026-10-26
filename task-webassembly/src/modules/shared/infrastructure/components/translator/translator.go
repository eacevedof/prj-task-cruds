package translator

import (
	"strings"
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
)

const DEFAULT_LANGUAGE_CODE = "en"

var textsByLanguageCode = map[string]map[string]string{
	"en": TextsEn,
	"es": TextsEs,
}

// Translator textos de la interfaz en el idioma activo (ingles por defecto). En el html: data-i18n="clave" (texto) y
// data-i18n-placeholder="clave" / data-i18n-aria-label="clave". Una sola instancia: guarda el idioma activo (quien lo
// persiste es el controller). Componente autocontenido: sus diccionarios viven en su misma carpeta.
type Translator struct {
	languageCode string
}

var instance *Translator

func GetInstance() *Translator {
	if instance == nil {
		instance = &Translator{languageCode: DEFAULT_LANGUAGE_CODE}
	}
	return instance
}

func (t *Translator) GetLanguageCode() string {
	return t.languageCode
}

// SetLanguageCode un idioma sin diccionario cae en el de por defecto.
func (t *Translator) SetLanguageCode(languageCode string) {
	if _, ok := textsByLanguageCode[languageCode]; !ok {
		languageCode = DEFAULT_LANGUAGE_CODE
	}
	t.languageCode = languageCode
}

// GetText replacements: {"title": "x"} sustituye "{title}" (nil = sin sustituciones). Sin traduccion: ingles; sin
// ingles: la propia clave (se ve el hueco, no rompe).
func (t *Translator) GetText(key string, replacements map[string]any) string {
	text, ok := textsByLanguageCode[t.languageCode][key]
	if !ok {
		text, ok = TextsEn[key]
	}
	if !ok {
		text = key
	}
	for name, value := range replacements {
		text = strings.ReplaceAll(text, "{"+name+"}", application.ToString(value))
	}
	return text
}

// TranslateElement root: el document o cualquier elemento (ParentNode).
func (t *Translator) TranslateElement(root js.Value) {
	t.forEachElement(root, "[data-i18n]", func(element js.Value) {
		element.Set("textContent", t.GetText(element.Get("dataset").Get("i18n").String(), nil))
	})
	t.forEachElement(root, "[data-i18n-placeholder]", func(element js.Value) {
		element.Set("placeholder", t.GetText(element.Get("dataset").Get("i18nPlaceholder").String(), nil))
	})
	t.forEachElement(root, "[data-i18n-aria-label]", func(element js.Value) {
		element.Call("setAttribute", "aria-label", t.GetText(element.Get("dataset").Get("i18nAriaLabel").String(), nil))
	})
}

func (t *Translator) forEachElement(root js.Value, selector string, onElement func(element js.Value)) {
	elements := root.Call("querySelectorAll", selector)
	for i := 0; i < elements.Length(); i++ {
		onElement(elements.Index(i))
	}
}
