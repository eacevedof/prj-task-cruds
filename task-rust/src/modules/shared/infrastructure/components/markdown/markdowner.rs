use std::sync::LazyLock;

use regex::{Captures, Regex};

const BLOCK_TAG_PREFIXES: [&str; 7] = ["<h", "<ul>", "<ol>", "<pre>", "<blockquote>", "<hr>", "<table"];

static CODE_BLOCK_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"```(\w*)\n?([\s\S]*?)```").unwrap());
static INLINE_CODE_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"`([^`\n]+)`").unwrap());
static IMG_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"!\[([^\]]*)\]\(([^)]+)\)").unwrap());
static A_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"\[([^\]]+)\]\(([^)]+)\)").unwrap());
static H_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"(?m)^(#{1,6})\s+(.*)$").unwrap());
static TAG_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"<[^>]*>").unwrap());
static ANCHOR_INVALID_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"[^\p{L}\p{N}.]+").unwrap());
static BLOCKQUOTE_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"(?m)^> (.*)$").unwrap());
static HR_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"(?m)^(---|\*\*\*|___)\s*$").unwrap());
static UL_ITEM_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"^\s*[-*+]\s+(.*)$").unwrap());
static OL_ITEM_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"^\s*\d+\.\s+(.*)$").unwrap());
static TABLE_SEPARATOR_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"^\|[\s:|-]+\|$").unwrap());
static MANY_NEW_LINES_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"\n\n+").unwrap());

const WORD_BYTES: &str = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_";

/// una regla de negrita/cursiva/tachado. Las de task-php llevan lookbehind/lookahead, que el crate regex no tiene:
/// not_before/not_after son los bytes que no pueden ir justo antes/despues del match.
struct TextFormattingRule {
    pattern: Regex,
    replacement: &'static str,
    not_before: &'static str,
    not_after: &'static str,
}

static TEXT_FORMATTING_RULES: LazyLock<Vec<TextFormattingRule>> = LazyLock::new(|| {
    let rule = |pattern: &str, replacement: &'static str, not_before: &'static str, not_after: &'static str| {
        TextFormattingRule { pattern: Regex::new(pattern).unwrap(), replacement, not_before, not_after }
    };
    vec![
        rule(r"^\*\*\*([^*\n]+)\*\*\*", "<strong><em>${1}</em></strong>", "", ""),
        rule(r"^___([^_\n]+)___", "<strong><em>${1}</em></strong>", "", ""),
        rule(r"^\*\*([^*\n]+)\*\*", "<strong>${1}</strong>", "", ""),
        rule(r"^__([^_\n]+)__", "<strong>${1}</strong>", "_", "_"),
        rule(r"^\*([^*\n]+)\*", "<em>${1}</em>", "*", "*"),
        // cursiva con _ solo entre espacios/bordes: "user_uuid" o "api_token" no son cursiva
        rule(r"^_([^_\n]+)_", "<em>${1}</em>", WORD_BYTES, WORD_BYTES),
        rule(r"^~~([^~\n]+)~~", "<del>${1}</del>", "", ""),
    ]
});

/// Markdown -> HTML sin vendor (port del de task-php). Cubre lo que usa la documentacion: titulos (con id para
/// enlazar #anclas), negrita/cursiva/tachado, enlaces, imagenes, citas, <hr>, listas, tablas GFM y codigo (en bloque
/// y en linea, escapado). El resto del texto NO se escapa: el .md es nuestro.
#[derive(Default)]
pub struct Markdowner {
    pre_and_code_blocks: Vec<String>,
    inline_code_blocks: Vec<String>,
}

impl Markdowner {
    pub fn get_instance() -> Self {
        Self::default()
    }

    pub fn get_html_from_markdown(&mut self, markdown: &str) -> String {
        self.pre_and_code_blocks.clear();
        self.inline_code_blocks.clear();

        // un checkout en Windows (autocrlf) deja CRLF y los patrones de linea ($) no casarian
        let mut html = self.get_with_code_placeholders(&markdown.replace("\r\n", "\n"));
        // imagenes antes que enlaces: "![alt](src)" contiene un "[alt](src)"
        html = self.get_img_tags(&html);
        html = self.get_a_tags(&html);
        html = self.get_h_tags(&html);
        html = self.get_text_formatting_tags(&html);
        html = self.get_blockquote_tags(&html);
        html = self.get_hr_tags(&html);
        html = self.get_list_tags(&html);
        // el codigo se restaura antes de tablas y parrafos: puede ir dentro de una celda
        html = self.get_with_code_restored(&html);
        html = self.get_table_tags(&html);
        self.get_p_tags(&html)
    }

    /// el codigo se aparta con un placeholder para que ninguna otra regla toque su contenido.
    fn get_with_code_placeholders(&mut self, markdown: &str) -> String {
        let markdown = CODE_BLOCK_PATTERN
            .replace_all(markdown, |captures: &Captures| {
                let language = if captures[1].is_empty() {
                    String::new()
                } else {
                    format!(" class=\"language-{}\"", &captures[1])
                };
                self.pre_and_code_blocks
                    .push(format!("<pre><code{language}>{}</code></pre>", get_escaped_html(&captures[2])));
                get_code_block_placeholder(self.pre_and_code_blocks.len() - 1)
            })
            .to_string();

        INLINE_CODE_PATTERN
            .replace_all(&markdown, |captures: &Captures| {
                self.inline_code_blocks.push(format!("<code>{}</code>", get_escaped_html(&captures[1])));
                get_inline_code_placeholder(self.inline_code_blocks.len() - 1)
            })
            .to_string()
    }

    fn get_img_tags(&self, html: &str) -> String {
        IMG_PATTERN.replace_all(html, r#"<img alt="${1}" src="${2}">"#).to_string()
    }

    fn get_a_tags(&self, html: &str) -> String {
        A_PATTERN.replace_all(html, r#"<a href="${2}">${1}</a>"#).to_string()
    }

    fn get_h_tags(&self, html: &str) -> String {
        H_PATTERN
            .replace_all(html, |captures: &Captures| {
                let level = captures[1].len();
                format!("<h{level} id=\"{}\">{}</h{level}>", self.get_anchor_id(&captures[2]), &captures[2])
            })
            .to_string()
    }

    /// "2.1 Alta de usuario" -> "2-1-alta-de-usuario"; los puntos de una version se mantienen ("1.0.0").
    fn get_anchor_id(&self, title: &str) -> String {
        let anchor_id = TAG_PATTERN.replace_all(title, "").to_lowercase();
        let anchor_id = ANCHOR_INVALID_PATTERN.replace_all(&anchor_id, "-");
        anchor_id.trim_matches(|char| char == '-' || char == '.').to_string()
    }

    fn get_text_formatting_tags(&self, html: &str) -> String {
        let mut html = html.to_string();
        for rule in TEXT_FORMATTING_RULES.iter() {
            html = self.get_replaced_by_rule(&html, rule);
        }
        html
    }

    /// como preg_replace: prueba el patron en cada posicion y, si casa y se cumplen los bytes de alrededor, lo
    /// sustituye y sigue tras el match; si no, avanza un caracter. Los patrones no pueden casar de dos formas en la
    /// misma posicion (la clase excluye el delimitador), asi que el resultado es el mismo que con lookarounds.
    fn get_replaced_by_rule(&self, html: &str, rule: &TextFormattingRule) -> String {
        let mut replaced = String::with_capacity(html.len());
        let mut position = 0;
        while position < html.len() {
            if let Some(captures) = rule.pattern.captures(&html[position..]) {
                let end = position + captures.get(0).map_or(0, |whole_match| whole_match.end());
                if self.is_rule_context_ok(html, position, end, rule) {
                    captures.expand(rule.replacement, &mut replaced);
                    position = end;
                    continue;
                }
            }
            let char = html[position..].chars().next().unwrap_or_default();
            replaced.push(char);
            position += char.len_utf8();
        }
        replaced
    }

    fn is_rule_context_ok(&self, html: &str, start: usize, end: usize, rule: &TextFormattingRule) -> bool {
        let bytes = html.as_bytes();
        if !rule.not_before.is_empty() && start > 0 && rule.not_before.as_bytes().contains(&bytes[start - 1]) {
            return false;
        }
        if !rule.not_after.is_empty() && end < bytes.len() && rule.not_after.as_bytes().contains(&bytes[end]) {
            return false;
        }
        true
    }

    fn get_blockquote_tags(&self, html: &str) -> String {
        BLOCKQUOTE_PATTERN.replace_all(html, "<blockquote>${1}</blockquote>").to_string()
    }

    fn get_hr_tags(&self, html: &str) -> String {
        HR_PATTERN.replace_all(html, "<hr>").to_string()
    }

    fn get_list_tags(&self, html: &str) -> String {
        let mut list_lines: Vec<String> = vec![];
        let mut open_list_tag = "";
        for line in html.split('\n') {
            let (list_tag, item) = if let Some(captures) = UL_ITEM_PATTERN.captures(line) {
                ("ul", captures[1].to_string())
            } else if let Some(captures) = OL_ITEM_PATTERN.captures(line) {
                ("ol", captures[1].to_string())
            } else {
                ("", String::new())
            };

            if !open_list_tag.is_empty() && list_tag != open_list_tag {
                list_lines.push(format!("</{open_list_tag}>"));
                open_list_tag = "";
            }
            if list_tag.is_empty() {
                if !line.trim().is_empty() {
                    list_lines.push(line.to_string());
                }
                continue;
            }
            if open_list_tag.is_empty() {
                list_lines.push(format!("<{list_tag}>"));
                open_list_tag = list_tag;
            }
            list_lines.push(format!("<li>{item}</li>"));
        }
        if !open_list_tag.is_empty() {
            list_lines.push(format!("</{open_list_tag}>"));
        }
        list_lines.join("\n")
    }

    fn get_with_code_restored(&self, html: &str) -> String {
        let mut html = html.to_string();
        for (index, inline_code_block) in self.inline_code_blocks.iter().enumerate() {
            html = html.replace(&get_inline_code_placeholder(index), inline_code_block);
        }
        for (index, pre_and_code_block) in self.pre_and_code_blocks.iter().enumerate() {
            html = html.replace(&get_code_block_placeholder(index), pre_and_code_block);
        }
        html
    }

    /// tabla = fila "| a | b |" seguida de una separadora "|---|:--:|" (que fija la alineacion).
    fn get_table_tags(&self, html: &str) -> String {
        let lines: Vec<&str> = html.split('\n').collect();
        let mut table_lines: Vec<String> = vec![];
        let mut index = 0;
        while index < lines.len() {
            let line = lines[index].trim();
            let is_table_start = line.starts_with('|')
                && index + 1 < lines.len()
                && TABLE_SEPARATOR_PATTERN.is_match(lines[index + 1].trim());
            if !is_table_start {
                table_lines.push(lines[index].to_string());
                index += 1;
                continue;
            }

            let alignments = self.get_table_alignments(lines[index + 1].trim());
            let mut table_html = format!(
                "<table class=\"md-table\">\n<thead>\n<tr>{}</tr>\n</thead>\n<tbody>",
                self.get_table_cells(line, "th", &alignments)
            );
            index += 2;
            while index < lines.len() && lines[index].trim().starts_with('|') {
                table_html += &format!("\n<tr>{}</tr>", self.get_table_cells(lines[index].trim(), "td", &alignments));
                index += 1;
            }
            table_lines.push(table_html + "\n</tbody>\n</table>");
        }
        table_lines.join("\n")
    }

    fn get_table_cells(&self, row: &str, cell_tag: &str, alignments: &[&str]) -> String {
        let mut cells_html = String::new();
        for (index, cell) in self.get_table_row_cells(row).iter().enumerate() {
            let style = match alignments.get(index) {
                Some(alignment) if !alignment.is_empty() => format!(" style=\"text-align:{alignment}\""),
                _ => String::new(),
            };
            cells_html += &format!("<{cell_tag}{style}>{}</{cell_tag}>", cell.trim());
        }
        cells_html
    }

    fn get_table_row_cells<'a>(&self, row: &'a str) -> Vec<&'a str> {
        row.trim().trim_matches('|').split('|').collect()
    }

    fn get_table_alignments(&self, separator_row: &str) -> Vec<&'static str> {
        self.get_table_row_cells(separator_row)
            .iter()
            .map(|cell| {
                let cell = cell.trim();
                match (cell.starts_with(':'), cell.ends_with(':')) {
                    (true, true) => "center",
                    (false, true) => "right",
                    (true, false) => "left",
                    (false, false) => "",
                }
            })
            .collect()
    }

    fn get_p_tags(&self, html: &str) -> String {
        MANY_NEW_LINES_PATTERN
            .replace_all(html, "\n\n")
            .split("\n\n")
            .filter(|paragraph| !paragraph.trim().is_empty())
            .map(|paragraph| self.get_paragraph_html(paragraph.trim()))
            .collect::<Vec<String>>()
            .join("\n")
    }

    fn get_paragraph_html(&self, paragraph: &str) -> String {
        if BLOCK_TAG_PREFIXES.iter().any(|block_tag_prefix| paragraph.starts_with(block_tag_prefix)) {
            return paragraph.to_string();
        }
        if paragraph.contains("<table") || paragraph.contains("<pre><code") {
            return paragraph.to_string();
        }
        format!("<p>{}</p>", paragraph.replace('\n', "<br>"))
    }
}

/// htmlspecialchars(ENT_QUOTES): mismas entidades que PHP (&#039; y &quot;).
pub fn get_escaped_html(code: &str) -> String {
    code.replace('&', "&amp;").replace('<', "&lt;").replace('>', "&gt;").replace('"', "&quot;").replace('\'', "&#039;")
}

fn get_code_block_placeholder(index: usize) -> String {
    format!("XZCODEBLOCKX{index}XPLACEHOLDERX")
}

fn get_inline_code_placeholder(index: usize) -> String {
    format!("XZINLINECODEX{index}XPLACEHOLDERX")
}
