use crate::modules::open::documentation::infrastructure::views::documentation_view_dto::DocumentationViewDto;
use crate::modules::shared::infrastructure::components::markdown::markdowner::Markdowner;
use crate::modules::shared::infrastructure::views::abstract_view::AbstractView;

const DOCUMENTATION_TITLE: &str = "Task Rust CRUD API";

/// el .md va dentro del binario (include_str!)
const DOCUMENTATION_MARKDOWN: &str = include_str!("documentation.md");

/// publica documentation.md: TODA la documentacion de la API vive en ese .md (aqui no hay texto).
pub struct DocumentationView {
    markdowner: Markdowner,
}

impl AbstractView for DocumentationView {}

impl DocumentationView {
    pub fn get_instance() -> Self {
        Self { markdowner: Markdowner::get_instance() }
    }

    pub fn get_html(&mut self, documentation_view_dto: &DocumentationViewDto) -> String {
        let documentation = self.get_replaced_content(
            DOCUMENTATION_MARKDOWN,
            &[
                ("{{appVersion}}", documentation_view_dto.get_app_version()),
                ("{{appVersionUpdate}}", documentation_view_dto.get_app_version_update()),
                ("{{appBaseUrl}}", documentation_view_dto.get_app_base_url()),
            ],
        );
        let html_body = self.markdowner.get_html_from_markdown(&documentation);
        self.get_html_page(DOCUMENTATION_TITLE, &html_body)
    }
}
