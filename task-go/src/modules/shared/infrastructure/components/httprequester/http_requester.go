package httprequester

import (
	"encoding/json"
	"io"
	"net"
	"net/http"
	"regexp"
	"slices"
	"strings"
	"time"
	_ "time/tzdata"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/useragent"
)

const userAgentMaxLength = 500

var bearerTokenPattern = regexp.MustCompile(`(?i)^Bearer\s+(\S+)$`)

// HttpRequester UNICO punto de lectura de la peticion HTTP: body, query, cabeceras y la metadata que viaja con todo
// dto de entrada (AbstractInputDto). Nadie mas lee el *http.Request. Uno por peticion (en PHP leia las superglobales).
type HttpRequester struct {
	request *http.Request
	post    map[string]any
}

func GetInstance(request *http.Request) *HttpRequester {
	return &HttpRequester{request: request, post: map[string]any{}}
}

// LoadJsonBodyIntoPost un form-data en POST se lee como tal; la API habla JSON (tambien en PUT/PATCH/DELETE):
// se decodifica el body y, si no es un objeto JSON, el post queda vacio.
func (h *HttpRequester) LoadJsonBodyIntoPost() {
	if len(h.post) > 0 {
		return
	}
	if h.isFormPost() {
		if err := h.request.ParseForm(); err == nil {
			for key, values := range h.request.PostForm {
				h.post[key] = values[len(values)-1]
			}
		}
		if len(h.post) > 0 {
			return
		}
	}
	if h.request.Body == nil {
		return
	}
	rawBody, err := io.ReadAll(h.request.Body)
	if err != nil || len(rawBody) == 0 {
		return
	}

	var body map[string]any
	if err := json.Unmarshal(rawBody, &body); err != nil || body == nil {
		h.post = map[string]any{}
		return
	}
	h.post = body
}

// GetPost el body entero.
func (h *HttpRequester) GetPost() map[string]any {
	return h.post
}

// GetPostValue un campo del body, o defaultValue si no viene.
func (h *HttpRequester) GetPostValue(key string, defaultValue any) any {
	value, isSet := h.post[key]
	if !isSet || value == nil {
		return defaultValue
	}
	return value
}

// GetPostString campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio
// (y lo rechaza la validacion de campos obligatorios) en vez de dar "Array" o un error de tipo.
func (h *HttpRequester) GetPostString(key string) string {
	return getScalarAsString(h.GetPostValue(key, ""))
}

// GetGet la query entera (el ultimo valor de cada clave, como $_GET).
func (h *HttpRequester) GetGet() map[string]any {
	query := map[string]any{}
	for key, values := range h.request.URL.Query() {
		query[key] = values[len(values)-1]
	}
	return query
}

func (h *HttpRequester) GetGetString(key string) string {
	return getScalarAsString(h.GetGet()[key])
}

func (h *HttpRequester) GetRequestMethod() string {
	method := strings.ToLower(strings.TrimSpace(h.request.Method))
	if method == "" {
		return "get"
	}
	return method
}

func (h *HttpRequester) GetRequestUri() string {
	if requestUri := h.request.URL.RequestURI(); requestUri != "" {
		return requestUri
	}
	return "/"
}

func (h *HttpRequester) GetHeaderValueByKey(key string) string {
	return strings.TrimSpace(h.request.Header.Get(key))
}

func (h *HttpRequester) GetBearerToken() string {
	matches := bearerTokenPattern.FindStringSubmatch(h.GetHeaderValueByKey("Authorization"))
	if matches == nil {
		return ""
	}
	return matches[1]
}

func (h *HttpRequester) GetMetadata() map[string]any {
	userAgent := h.GetUserAgent()
	userAgentParser := useragent.GetInstance()
	return map[string]any{
		"userIpAddress":      h.GetClientIp(),
		"userBrowser":        userAgentParser.GetBrowserName(userAgent),
		"userBrowserVersion": userAgentParser.GetBrowserVersion(userAgent),
		"userOs":             userAgentParser.GetOsName(userAgent),
		"userTimeZone":       h.GetTimeZone(),
		"userLanguageCode":   h.GetLanguageCode(),
	}
}

func (h *HttpRequester) GetUserAgent() string {
	userAgent := []rune(h.GetHeaderValueByKey("User-Agent"))
	if len(userAgent) > userAgentMaxLength {
		userAgent = userAgent[:userAgentMaxLength]
	}
	return string(userAgent)
}

func (h *HttpRequester) GetClientIp() string {
	if ip := h.request.Header.Get("Client-Ip"); ip != "" {
		return ip
	}
	// "cliente, proxy1, proxy2": el cliente es el primer salto
	if ip := strings.TrimSpace(strings.Split(h.request.Header.Get("X-Forwarded-For"), ",")[0]); ip != "" {
		return ip
	}
	if remoteAddr := h.request.RemoteAddr; remoteAddr != "" {
		if host, _, err := net.SplitHostPort(remoteAddr); err == nil {
			return host
		}
		return remoteAddr
	}
	return "127.0.0.1"
}

// GetTimeZone cabecera opcional "X-Time-Zone: Europe/Madrid". Solo si es una zona IANA valida.
func (h *HttpRequester) GetTimeZone() string {
	timeZone := h.GetHeaderValueByKey("X-Time-Zone")
	if timeZone == "" || timeZone == "Local" {
		return ""
	}
	if _, err := time.LoadLocation(timeZone); err != nil {
		return ""
	}
	return timeZone
}

// GetLanguageCode "es-ES,es;q=0.9,en;q=0.8" -> "es": la primera preferencia, si la API la entiende.
func (h *HttpRequester) GetLanguageCode() string {
	languageCode := strings.TrimSpace(strings.Split(h.GetHeaderValueByKey("Accept-Language"), ",")[0])
	if len(languageCode) > 2 {
		languageCode = languageCode[:2]
	}
	languageCode = strings.ToLower(languageCode)
	if slices.Contains(enums.LanguageCodeEnum.VALUES, languageCode) {
		return languageCode
	}
	return enums.LanguageCodeEnum.EN
}

func (h *HttpRequester) isFormPost() bool {
	contentType := h.request.Header.Get("Content-Type")
	return h.request.Method == http.MethodPost &&
		(strings.HasPrefix(contentType, "application/x-www-form-urlencoded") || strings.HasPrefix(contentType, "multipart/form-data"))
}

func getScalarAsString(value any) string {
	switch value.(type) {
	case map[string]any, []any:
		return ""
	}
	return strings.TrimSpace(application.ToString(value))
}
