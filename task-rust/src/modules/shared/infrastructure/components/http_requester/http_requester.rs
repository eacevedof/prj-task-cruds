use std::net::SocketAddr;
use std::sync::LazyLock;

use chrono_tz::Tz;
use regex::Regex;
use serde_json::{Value, json};

use crate::modules::shared::application::primitives::{Primitives, get_primitives, to_string};
use crate::modules::shared::domain::enums::language_code_enum::LanguageCodeEnum;
use crate::modules::shared::infrastructure::components::user_agent::user_agent_parser::UserAgentParser;

const USER_AGENT_MAX_LENGTH: usize = 500;

static BEARER_TOKEN_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"(?i)^Bearer\s+(\S+)$").unwrap());

/// la peticion tal cual llega del servidor (o de un test): lo que en PHP son las superglobales.
#[derive(Debug, Clone, Default)]
pub struct HttpRequest {
    pub method: String,
    pub request_uri: String,
    pub headers: Vec<(String, String)>,
    pub body: Vec<u8>,
    /// ip del cliente sin puerto (el REMOTE_ADDR de PHP)
    pub remote_addr: String,
}

/// UNICO punto de lectura de la peticion HTTP: body, query, cabeceras y la metadata que viaja con todo dto de entrada
/// (AbstractInputDto). Nadie mas lee el HttpRequest. Uno por peticion (en PHP leia las superglobales).
#[derive(Debug, Clone, Default)]
pub struct HttpRequester {
    request: HttpRequest,
    post: Primitives,
}

impl HttpRequester {
    pub fn get_instance(request: HttpRequest) -> Self {
        Self { request, post: Primitives::new() }
    }

    /// un form-urlencoded en POST se lee como tal; la API habla JSON (tambien en PUT/PATCH/DELETE): se decodifica el
    /// body y, si no es un objeto JSON, el post queda vacio.
    pub fn load_json_body_into_post(&mut self) {
        if !self.post.is_empty() {
            return;
        }
        if self.is_form_post() {
            self.post = get_query_string_as_primitives(&String::from_utf8_lossy(&self.request.body));
            if !self.post.is_empty() {
                return;
            }
        }
        if self.request.body.is_empty() {
            return;
        }
        self.post = match serde_json::from_slice::<Value>(&self.request.body) {
            Ok(body @ Value::Object(_)) => get_primitives(body),
            _ => Primitives::new(),
        };
    }

    /// el body entero.
    pub fn get_post(&self) -> &Primitives {
        &self.post
    }

    /// un campo del body, o default_value si no viene.
    pub fn get_post_value(&self, key: &str, default_value: Value) -> Value {
        match self.post.get(key) {
            None | Some(Value::Null) => default_value,
            Some(value) => value.clone(),
        }
    }

    /// campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio (y lo rechaza la
    /// validacion de campos obligatorios) en vez de dar "Array" o un error de tipo.
    pub fn get_post_string(&self, key: &str) -> String {
        get_scalar_as_string(&self.get_post_value(key, json!("")))
    }

    /// la query entera (el ultimo valor de cada clave, como $_GET).
    pub fn get_get(&self) -> Primitives {
        let query_string = self.request.request_uri.split_once('?').map(|(_, query)| query).unwrap_or("");
        get_query_string_as_primitives(query_string.split('#').next().unwrap_or(""))
    }

    pub fn get_get_string(&self, key: &str) -> String {
        get_scalar_as_string(self.get_get().get(key).unwrap_or(&Value::Null))
    }

    pub fn get_request_method(&self) -> String {
        let method = self.request.method.trim().to_lowercase();
        if method.is_empty() { "get".to_string() } else { method }
    }

    pub fn get_request_uri(&self) -> String {
        if self.request.request_uri.is_empty() { "/".to_string() } else { self.request.request_uri.clone() }
    }

    pub fn get_header_value_by_key(&self, key: &str) -> String {
        self.request
            .headers
            .iter()
            .find(|(header_name, _)| header_name.eq_ignore_ascii_case(key))
            .map(|(_, header_value)| header_value.trim().to_string())
            .unwrap_or_default()
    }

    pub fn get_bearer_token(&self) -> String {
        BEARER_TOKEN_PATTERN
            .captures(&self.get_header_value_by_key("Authorization"))
            .map(|captures| captures[1].to_string())
            .unwrap_or_default()
    }

    pub fn get_metadata(&self) -> Primitives {
        let user_agent = self.get_user_agent();
        let user_agent_parser = UserAgentParser::get_instance();
        get_primitives(json!({
            "userIpAddress": self.get_client_ip(),
            "userBrowser": user_agent_parser.get_browser_name(&user_agent),
            "userBrowserVersion": user_agent_parser.get_browser_version(&user_agent),
            "userOs": user_agent_parser.get_os_name(&user_agent),
            "userTimeZone": self.get_time_zone(),
            "userLanguageCode": self.get_language_code(),
        }))
    }

    pub fn get_user_agent(&self) -> String {
        self.get_header_value_by_key("User-Agent").chars().take(USER_AGENT_MAX_LENGTH).collect()
    }

    pub fn get_client_ip(&self) -> String {
        let client_ip = self.get_header_value_by_key("Client-Ip");
        if !client_ip.is_empty() {
            return client_ip;
        }
        // "cliente, proxy1, proxy2": el cliente es el primer salto
        let forwarded_for = self.get_header_value_by_key("X-Forwarded-For");
        let first_hop = forwarded_for.split(',').next().unwrap_or("").trim();
        if !first_hop.is_empty() {
            return first_hop.to_string();
        }
        if !self.request.remote_addr.is_empty() {
            return match self.request.remote_addr.parse::<SocketAddr>() {
                Ok(socket_address) => socket_address.ip().to_string(),
                Err(_) => self.request.remote_addr.clone(),
            };
        }
        "127.0.0.1".to_string()
    }

    /// cabecera opcional "X-Time-Zone: Europe/Madrid". Solo si es una zona IANA valida.
    pub fn get_time_zone(&self) -> String {
        let time_zone = self.get_header_value_by_key("X-Time-Zone");
        if time_zone.is_empty() || time_zone.parse::<Tz>().is_err() {
            return String::new();
        }
        time_zone
    }

    /// "es-ES,es;q=0.9,en;q=0.8" -> "es": la primera preferencia, si la API la entiende.
    pub fn get_language_code(&self) -> String {
        let accept_language = self.get_header_value_by_key("Accept-Language");
        let language_code: String =
            accept_language.split(',').next().unwrap_or("").trim().chars().take(2).collect::<String>().to_lowercase();
        if LanguageCodeEnum::VALUES.contains(&language_code.as_str()) {
            return language_code;
        }
        LanguageCodeEnum::EN.to_string()
    }

    fn is_form_post(&self) -> bool {
        let content_type = self.get_header_value_by_key("Content-Type");
        self.request.method.eq_ignore_ascii_case("post")
            && content_type.starts_with("application/x-www-form-urlencoded")
    }
}

/// urldecode(): %XX -> byte y, si plus_as_space (query y form), "+" -> espacio. Un %XX mal formado se deja tal cual.
pub fn get_url_decoded(text: &str, plus_as_space: bool) -> String {
    let bytes = text.as_bytes();
    let mut decoded = Vec::with_capacity(bytes.len());
    let mut index = 0;
    while index < bytes.len() {
        match bytes[index] {
            b'+' if plus_as_space => decoded.push(b' '),
            b'%' if index + 2 < bytes.len()
                && bytes[index + 1].is_ascii_hexdigit()
                && bytes[index + 2].is_ascii_hexdigit() =>
            {
                let hex_pair = std::str::from_utf8(&bytes[index + 1..index + 3]).unwrap_or("00");
                decoded.push(u8::from_str_radix(hex_pair, 16).unwrap_or(0));
                index += 2;
            }
            byte => decoded.push(byte),
        }
        index += 1;
    }
    String::from_utf8_lossy(&decoded).to_string()
}

/// "a=1&b=dos" -> {"a": "1", "b": "dos"} (el ultimo valor de cada clave, como $_GET / $_POST).
fn get_query_string_as_primitives(query_string: &str) -> Primitives {
    let mut primitives = Primitives::new();
    for pair in query_string.split('&').filter(|pair| !pair.is_empty()) {
        let (key, value) = pair.split_once('=').unwrap_or((pair, ""));
        let key = get_url_decoded(key, true);
        if key.is_empty() {
            continue;
        }
        primitives.insert(key, Value::from(get_url_decoded(value, true)));
    }
    primitives
}

fn get_scalar_as_string(value: &Value) -> String {
    match value {
        Value::Array(_) | Value::Object(_) => String::new(),
        _ => to_string(value).trim().to_string(),
    }
}
