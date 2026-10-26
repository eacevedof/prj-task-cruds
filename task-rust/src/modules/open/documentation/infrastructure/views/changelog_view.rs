use crate::modules::open::documentation::infrastructure::views::documentation_view_dto::DocumentationViewDto;
use crate::modules::shared::infrastructure::components::markdown::markdowner::Markdowner;
use crate::modules::shared::infrastructure::views::abstract_view::AbstractView;

const CHANGELOG_TITLE: &str = "Task Rust CRUD API - changelog";

const CHANGELOG_MARKDOWN: &str = include_str!("changelog.md");

/// publica changelog.md (una seccion por version; el titulo de la doc enlaza a #<version>).
pub struct ChangelogView {
    markdowner: Markdowner,
}

impl AbstractView for ChangelogView {}

impl ChangelogView {
    pub fn get_instance() -> Self {
        Self { markdowner: Markdowner::get_instance() }
    }

    pub fn get_html(&mut self, documentation_view_dto: &DocumentationViewDto) -> String {
        let changelog = self
            .get_replaced_content(CHANGELOG_MARKDOWN, &[("{{appBaseUrl}}", documentation_view_dto.get_app_base_url())]);
        let html_body = self.markdowner.get_html_from_markdown(&changelog);
        self.get_html_page(CHANGELOG_TITLE, &html_body)
    }
}
