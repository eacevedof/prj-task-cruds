from Modules.Open.Documentation.Infrastructure.Controllers.ChangelogController import ChangelogController
from Modules.Open.Documentation.Infrastructure.Controllers.DocumentationController import DocumentationController


class DocumentationControllerMother:

    @staticmethod
    def getControllerInstance() -> DocumentationController:
        return DocumentationController()

    @staticmethod
    def getChangelogControllerInstance() -> ChangelogController:
        return ChangelogController()
