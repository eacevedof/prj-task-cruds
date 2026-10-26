use std::fs;
use std::io::Cursor;
use std::path::{Component, Path, PathBuf};

use tiny_http::{Header, Request, Response, StatusCode};

use crate::core::boot::boot_paths_enum::BOOT_PATHS_ENUM;

/// tipos de los ficheros de public/ (el mime.TypeByExtension de Go para los que hay)
const CONTENT_TYPES: [(&str, &str); 10] = [
    ("css", "text/css; charset=utf-8"),
    ("js", "text/javascript; charset=utf-8"),
    ("html", "text/html; charset=utf-8"),
    ("json", "application/json"),
    ("txt", "text/plain; charset=utf-8"),
    ("svg", "image/svg+xml"),
    ("png", "image/png"),
    ("jpg", "image/jpeg"),
    ("ico", "image/vnd.microsoft.icon"),
    ("woff2", "font/woff2"),
];

/// lo que en task-php hace nginx: si la url es un fichero de public/ se sirve tal cual; si no, pasa al front
/// controller (try_files $uri @rewriteapp). Los ficheros ocultos (.env, .git...) nunca: 404.
pub struct PublicFilesServer;

impl PublicFilesServer {
    pub fn get_instance() -> Self {
        Self
    }

    /// la peticion si NO es suya (la atiende IndexMain), o None si ya la ha respondido.
    pub fn get_request_if_not_public_file(&self, request: Request) -> Option<Request> {
        let url_path = request.url().split(['?', '#']).next().unwrap_or("").to_string();
        if url_path.contains("/.") {
            let _ = request.respond(Response::from_string("404 page not found\n").with_status_code(404));
            return None;
        }

        let Some(path_file) = self.get_path_public_file(&url_path) else {
            return Some(request);
        };
        let Ok(content) = fs::read(&path_file) else {
            return Some(request);
        };
        let content_length = content.len();
        let mut response = Response::new(StatusCode(200), vec![], Cursor::new(content), Some(content_length), None);
        let extension = path_file.extension().map(|extension| extension.to_string_lossy().to_lowercase());
        let content_type = CONTENT_TYPES
            .iter()
            .find(|(content_extension, _)| Some(content_extension.to_string()) == extension)
            .map_or("application/octet-stream", |(_, content_type)| content_type);
        if let Ok(header) = Header::from_bytes("Content-Type", content_type) {
            response.add_header(header);
        }
        let _ = request.respond(response);
        None
    }

    /// la ruta del fichero dentro de public/, o None si no existe, no es un fichero o se sale de public/.
    fn get_path_public_file(&self, url_path: &str) -> Option<PathBuf> {
        let relative_path = Path::new(url_path.trim_start_matches('/'));
        if relative_path.components().any(|component| !matches!(component, Component::Normal(_))) {
            return None;
        }
        let path_file = BOOT_PATHS_ENUM.PATH_PUBLIC.join(relative_path);
        if url_path.trim_start_matches('/').is_empty() || !path_file.is_file() {
            return None;
        }
        Some(path_file)
    }
}
