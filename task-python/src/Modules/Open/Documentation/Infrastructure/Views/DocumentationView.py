from pathlib import Path

from Modules.Shared.Infrastructure.Components.Markdown.Markdowner import Markdowner
from Modules.Shared.Infrastructure.Views.AbstractView import AbstractView

from Modules.Open.Documentation.Infrastructure.Views.DocumentationViewDto import DocumentationViewDto

TITLE = "Task Python CRUD API"


class DocumentationView(AbstractView):
    """
    Publica documentation.md: TODA la documentacion de la API vive en ese .md (aqui no hay texto).
    """

    def __init__(self):
        super().__init__()
        self._markdowner = Markdowner.getInstance()

    @staticmethod
    def getInstance() -> "DocumentationView":
        return DocumentationView()

    def getHtml(self, documentationViewDto: DocumentationViewDto) -> str:
        markdown = self.getReplacedContent((Path(__file__).parent / "documentation.md").read_text(encoding="utf-8"), {
            "{{appVersion}}": documentationViewDto.getAppVersion(),
            "{{appVersionUpdate}}": documentationViewDto.getAppVersionUpdate(),
            "{{appBaseUrl}}": documentationViewDto.getAppBaseUrl(),
        })
        return self.getHtmlPage(TITLE, self._markdowner.getHtmlFromMarkdown(markdown))
