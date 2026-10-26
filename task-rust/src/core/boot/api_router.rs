use std::collections::HashMap;
use std::sync::{Arc, LazyLock};

use regex::Regex;

use crate::modules::shared::infrastructure::components::http_requester::http_requester::{
    HttpRequester, get_url_decoded,
};
use crate::modules::shared::infrastructure::components::http_responder::http_responder::HttpResponder;

/// lo que ejecuta una ruta: el HttpRequester de la peticion y los tags de la url (":nombre").
pub type RouteController = fn(Arc<HttpRequester>, &HashMap<String, String>) -> HttpResponder;

/// una entrada de src/core/routes. allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
pub struct Route {
    pub url: &'static str,
    pub controller: RouteController,
    pub allowed: Vec<&'static str>,
}

/// lo que devuelve get_run_data: la ruta casada con sus args, o los metodos permitidos si la url casa pero el
/// metodo no (405), o nada si no casa ninguna url (404).
pub struct RunData<'a> {
    pub route: Option<&'a Route>,
    pub args: HashMap<String, String>,
    pub allowed: Vec<&'static str>,
}

static ROUTE_TAG_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r":([a-zA-Z_][a-zA-Z0-9_]*)").unwrap());

/// router REST: casa por METODO + patron de url.
///   - url con tags ":nombre" -> se pasan al controller como args con ese nombre.
///   - get_run_data() devuelve la ruta + args; si la url casa pero el metodo no -> allowed (405); si no casa ninguna
///     url -> vacio (404).
pub struct ApiRouter<'a> {
    routes: &'a [Route],
}

impl<'a> ApiRouter<'a> {
    pub fn get_instance(routes: &'a [Route]) -> Self {
        Self { routes }
    }

    pub fn get_run_data(&self, request_method: &str, request_uri: &str) -> RunData<'a> {
        let mut request_method = request_method.to_lowercase();
        //RFC 9110: todo recurso que admite GET admite HEAD
        if request_method == "head" {
            request_method = "get".to_string();
        }
        let path = self.get_path_without_query_string(request_uri);

        let mut allowed_for_path: Vec<&'static str> = vec![];
        for route in self.routes {
            let Some(args) = self.get_args_if_path_matches(route.url, &path) else {
                continue;
            };

            let allowed = if route.allowed.is_empty() { vec!["get"] } else { route.allowed.clone() };
            if allowed.contains(&request_method.as_str()) {
                return RunData { route: Some(route), args, allowed: vec![] };
            }
            for method in allowed {
                if !allowed_for_path.contains(&method) {
                    allowed_for_path.push(method);
                }
            }
        }
        RunData { route: None, args: HashMap::new(), allowed: allowed_for_path }
    }

    fn get_path_without_query_string(&self, request_uri: &str) -> String {
        let path = request_uri.split(['?', '#']).next().unwrap_or("");
        let path = path.trim_end_matches('/');
        if path.is_empty() { "/".to_string() } else { path.to_string() }
    }

    fn get_args_if_path_matches(&self, route_url: &str, path: &str) -> Option<HashMap<String, String>> {
        let pattern = ROUTE_TAG_PATTERN.replace_all(&regex::escape(route_url), "(?P<$1>[^/]+)").to_string();
        let route_regex = Regex::new(&format!("^{pattern}$")).ok()?;
        let captures = route_regex.captures(path)?;

        let mut args = HashMap::new();
        for name in route_regex.capture_names().flatten() {
            if let Some(value) = captures.name(name) {
                args.insert(name.to_string(), get_url_decoded(value.as_str(), false).trim().to_string());
            }
        }
        Some(args)
    }
}
