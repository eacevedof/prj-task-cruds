from Core.config.global_ import __

from Modules.Open.Documentation.Infrastructure.Views.ChangelogView import ChangelogView
from Modules.Open.Documentation.Infrastructure.Views.DocumentationViewDto import DocumentationViewDto
from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import EnvironmentReaderRawRepository
from Modules.Shared.Infrastructure.Traits.LogTrait import LogTrait


class ChangelogController:
    """
    GET /api/v1/changelog  (publica, HTML generado desde changelog.md)
    """

    def __init__(self):
        self._logTrait = LogTrait.getInstance()

    @staticmethod
    def getInstance() -> "ChangelogController":
        return ChangelogController()

    def invoke(self) -> HttpResponder:
        try:
            return HttpResponder.html(
                ChangelogView.getInstance().getHtml(
                    DocumentationViewDto.fromPrimitives({
                        "appBaseUrl": EnvironmentReaderRawRepository.getInstance().getBaseUrl(),
                    })
                )
            )
        except Exception as e:
            self._logTrait.logException(getThrowable(e), "ChangelogController::invoke")
            return HttpResponder.html(
                f"<h1>{__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)}</h1>",
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
            )
