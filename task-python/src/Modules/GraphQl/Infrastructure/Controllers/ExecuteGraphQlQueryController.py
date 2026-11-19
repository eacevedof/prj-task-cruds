import json

from Core.config.global_ import __

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.GraphQl.Domain.Enums.GraphQlMessageEnum import GraphQlMessageEnum
from Modules.GraphQl.Application.ExecuteGraphQlQuery.ExecuteGraphQlQueryService import ExecuteGraphQlQueryService
from Modules.GraphQl.Application.ExecuteGraphQlQuery.ExecuteGraphQlRequestDto import ExecuteGraphQlRequestDto
from Modules.GraphQl.Application.ExecuteGraphQlQuery.ExecuteGraphQlResultDto import ExecuteGraphQlResultDto


class ExecuteGraphQlQueryController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "ExecuteGraphQlQueryController":
        return ExecuteGraphQlQueryController()

    def invoke(self) -> HttpResponder:
        executeGraphQlRequestDto = ExecuteGraphQlRequestDto.fromPrimitives(
            self.httpRequest.getPost()
        )

        if not executeGraphQlRequestDto.getQuery():
            return self._getGraphQlResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                ExecuteGraphQlResultDto.fromErrorMessage(
                    __(GraphQlMessageEnum.GRAPHQL_QUERY_IS_REQUIRED)
                )
            )

        try:
            executeGraphQlResultDto = ExecuteGraphQlQueryService.getInstance().invoke(
                executeGraphQlRequestDto
            )
            return self._getGraphQlResponse(ExceptionEnum.CODE_OK, executeGraphQlResultDto)
        except Exception as e:
            self.logTrait.logException(getThrowable(e), "ExecuteGraphQlQueryController::invoke")
            return self._getGraphQlResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                ExecuteGraphQlResultDto.fromErrorMessage(
                    __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)
                )
            )

    def _getGraphQlResponse(
        self,
        httpCode: int,
        graphqlResultDto: ExecuteGraphQlResultDto
    ) -> HttpResponder:
        # ensure_ascii=False + separators: el JSON.stringify de Node (sin espacios, UTF-8 tal cual)
        return HttpResponder.json(json.dumps(graphqlResultDto.toArray(), ensure_ascii=False, separators=(",", ":")), httpCode)
