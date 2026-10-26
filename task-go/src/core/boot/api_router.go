package boot

import (
	"net/url"
	"regexp"
	"slices"
	"strings"

	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
)

// RouteController lo que ejecuta una ruta: el HttpRequester de la peticion y los tags de la url (":nombre").
type RouteController func(httpRequester *httprequester.HttpRequester, args map[string]string) *httpresponder.HttpResponder

// Route una entrada de src/core/routes. Allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
type Route struct {
	Url        string
	Controller RouteController
	Allowed    []string
}

// RunData lo que devuelve GetRunData: la ruta casada con sus args, o los metodos permitidos si la url casa
// pero el metodo no (405), o nada si no casa ninguna url (404).
type RunData struct {
	Route   *Route
	Args    map[string]string
	Allowed []string
}

// ApiRouter router REST: casa por METODO + patron de url.
//   - url con tags ":nombre" -> se pasan al controller como args con ese nombre.
//   - GetRunData() devuelve la ruta + args; si la url casa pero el metodo no -> Allowed (405); si no casa
//     ninguna url -> vacio (404).
type ApiRouter struct {
	routes []Route
}

var routeTagPattern = regexp.MustCompile(`:([a-zA-Z_][a-zA-Z0-9_]*)`)

func ApiRouterGetInstance(routes []Route) *ApiRouter {
	return &ApiRouter{routes: routes}
}

func (r *ApiRouter) GetRunData(requestMethod string, requestUri string) RunData {
	requestMethod = strings.ToLower(requestMethod)
	//RFC 9110: todo recurso que admite GET admite HEAD
	if requestMethod == "head" {
		requestMethod = "get"
	}
	path := r.getPathWithoutQueryString(requestUri)

	var allowedForPath []string
	for i := range r.routes {
		route := &r.routes[i]
		args, isMatch := r.getArgsIfPathMatches(route.Url, path)
		if !isMatch {
			continue
		}

		allowed := route.Allowed
		if len(allowed) == 0 {
			allowed = []string{"get"}
		}
		if slices.Contains(allowed, requestMethod) {
			return RunData{Route: route, Args: args}
		}
		for _, method := range allowed {
			if !slices.Contains(allowedForPath, method) {
				allowedForPath = append(allowedForPath, method)
			}
		}
	}
	return RunData{Allowed: allowedForPath}
}

func (r *ApiRouter) getPathWithoutQueryString(requestUri string) string {
	path := requestUri
	if parsedUrl, err := url.Parse(requestUri); err == nil {
		path = parsedUrl.Path
	}
	path = strings.TrimRight(path, "/")
	if path == "" {
		return "/"
	}
	return path
}

func (r *ApiRouter) getArgsIfPathMatches(routeUrl string, path string) (map[string]string, bool) {
	pattern := routeTagPattern.ReplaceAllString(regexp.QuoteMeta(routeUrl), `(?P<$1>[^/]+)`)
	routeRegexp := regexp.MustCompile("^" + pattern + "$")
	matches := routeRegexp.FindStringSubmatch(path)
	if matches == nil {
		return nil, false
	}

	args := map[string]string{}
	for i, name := range routeRegexp.SubexpNames() {
		if name == "" {
			continue
		}
		value, err := url.PathUnescape(matches[i])
		if err != nil {
			value = matches[i]
		}
		args[name] = strings.TrimSpace(value)
	}
	return args, true
}
