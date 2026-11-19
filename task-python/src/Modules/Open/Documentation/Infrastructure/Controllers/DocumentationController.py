from Core.config.global_ import __

from Modules.Open.Documentation.Infrastructure.Views.DocumentationView import DocumentationView
from Modules.Open.Documentation.Infrastructure.Views.DocumentationViewDto import DocumentationViewDto
from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import EnvironmentReaderRawRepository
from Modules.Shared.Infrastructure.Traits.LogTrait import LogTrait


class DocumentationController:
    """
    GET /api/v1  (publica, HTML generado desde documentation.md)
    """

    def __init__(self):
        self._logTrait = LogTrait.getInstance()

    @staticmethod
    def getInstance() -> "DocumentationController":
        return DocumentationController()

    def invoke(self) -> HttpResponder:
        try:
            environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance()
            return HttpResponder.html(
                DocumentationView.getInstance().getHtml(
                    DocumentationViewDto.fromPrimitives({
                        "appVersion": environmentReaderRawRepository.getAppVersion(),
                        "appVersionUpdate": environmentReaderRawRepository.getAppVersionUpdate(),
                        "appBaseUrl": environmentReaderRawRepository.getBaseUrl(),
                    })
                )
            )
        except Exception as e:
            self._logTrait.logException(getThrowable(e), "DocumentationController::invoke")
            return HttpResponder.html(
                f"<h1>{__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)}</h1>",
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
            )
