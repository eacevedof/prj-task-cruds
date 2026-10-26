use crate::modules::shared::infrastructure::components::markdown::markdowner::get_escaped_html;

const CSS_PATH: &str = "/assets/Modules/Documentation/Css/documentation.css";
const JS_PATH: &str = "/assets/Modules/Documentation/Js/documentation.js";

/// esqueleto HTML de las paginas que se generan desde un .md. Solo metodos por defecto: la vista que lo implementa no
/// aporta nada.
pub trait AbstractView {
    fn get_html_page(&self, title: &str, html_body: &str) -> String {
        // htmlspecialchars(ENT_QUOTES): las mismas entidades que PHP para las comillas
        let title = get_escaped_html(title);
        format!(
            "<!DOCTYPE html>
<html lang=\"en\">
<head>
<meta charset=\"utf-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
<title>{title}</title>
<link rel=\"stylesheet\" href=\"{CSS_PATH}\">
</head>
<body>
{html_body}
<script src=\"{JS_PATH}\"></script>
</body>
</html>"
        )
    }

    /// rellena los {{placeholder}} del .md: [("{{appVersion}}", "2.0.0"), ...]
    fn get_replaced_content(&self, content: &str, to_replace: &[(&str, &str)]) -> String {
        let mut replaced_content = content.to_string();
        for (placeholder, value) in to_replace {
            replaced_content = replaced_content.replace(placeholder, value);
        }
        replaced_content
    }
}
