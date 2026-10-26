use std::sync::LazyLock;

use regex::Regex;

/// el ORDEN importa: Edge y Opera tambien dicen "Chrome", Chrome tambien dice "Safari".
/// nombre => patron cuyo grupo 1 es la version.
static BROWSER_PATTERNS: LazyLock<Vec<(&str, Regex)>> = LazyLock::new(|| {
    vec![
        ("Edge", Regex::new(r"Edg(?:e|A|iOS)?/([\d.]+)").unwrap()),
        ("Opera", Regex::new(r"(?:OPR|Opera)/([\d.]+)").unwrap()),
        ("Firefox", Regex::new(r"(?:Firefox|FxiOS)/([\d.]+)").unwrap()),
        ("Chrome", Regex::new(r"(?:Chrome|CriOS)/([\d.]+)").unwrap()),
        ("Safari", Regex::new(r"Version/([\d.]+).*Safari/").unwrap()),
        ("Postman", Regex::new(r"PostmanRuntime/([\d.]+)").unwrap()),
        ("curl", Regex::new(r"^curl/([\d.]+)").unwrap()),
    ]
});

/// el ORDEN importa: Android dice "Linux", iPhone/iPad dicen "Mac OS X".
static OS_PATTERNS: LazyLock<Vec<(&str, Regex)>> = LazyLock::new(|| {
    vec![
        ("Android", Regex::new(r"Android").unwrap()),
        ("iOS", Regex::new(r"iPhone|iPad|iPod").unwrap()),
        ("Windows", Regex::new(r"Windows").unwrap()),
        ("Mac", Regex::new(r"Macintosh|Mac OS X").unwrap()),
        ("Ubuntu", Regex::new(r"Ubuntu").unwrap()),
        ("Linux", Regex::new(r"Linux").unwrap()),
    ]
});

/// navegador, version y sistema operativo a partir del User-Agent. Deteccion por patrones (sin vendor): suficiente
/// para metadata/trazas, no para decidir permisos (el User-Agent lo escribe el cliente).
pub struct UserAgentParser;

impl UserAgentParser {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_browser_name(&self, user_agent: &str) -> String {
        BROWSER_PATTERNS
            .iter()
            .find(|(_, pattern)| pattern.is_match(user_agent))
            .map(|(name, _)| name.to_string())
            .unwrap_or_default()
    }

    pub fn get_browser_version(&self, user_agent: &str) -> String {
        BROWSER_PATTERNS
            .iter()
            .find_map(|(_, pattern)| pattern.captures(user_agent).map(|captures| captures[1].to_string()))
            .unwrap_or_default()
    }

    pub fn get_os_name(&self, user_agent: &str) -> String {
        OS_PATTERNS
            .iter()
            .find(|(_, pattern)| pattern.is_match(user_agent))
            .map(|(name, _)| name.to_string())
            .unwrap_or_default()
    }
}
