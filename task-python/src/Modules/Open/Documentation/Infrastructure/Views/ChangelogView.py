from pathlib import Path

from Modules.Shared.Infrastructure.Components.Markdown.Markdowner import Markdowner
from Modules.Shared.Infrastructure.Views.AbstractView import AbstractView

from Modules.Open.Documentation.Infrastructure.Views.DocumentationViewDto import DocumentationViewDto

TITLE = "Task Python CRUD API - changelog"


class ChangelogView(AbstractView):
    """
    Publica changelog.md (una seccion por version; el titulo de la doc enlaza a #<version>).
    """

    def __init__(self):
        super().__init__()
        self._markdowner = Markdowner.getInstance()

    @staticmethod
    def getInstance() -> "ChangelogView":
        return ChangelogView()

    def getHtml(self, documentationViewDto: DocumentationViewDto) -> str:
        markdown = self.getReplacedContent((Path(__file__).parent / "changelog.md").read_text(encoding="utf-8"), {
            "{{appBaseUrl}}": documentationViewDto.getAppBaseUrl(),
        })
        return self.getHtmlPage(TITLE, self._markdowner.getHtmlFromMarkdown(markdown))
