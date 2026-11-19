import json
import sys
from typing import Any


class JsonResponseRepositoryMock:
    """
    Lectura del envelope de la API: {"code": int, "status": bool, "errors": string[], "data": object}
    """

    def __init__(self, responseContent: str):
        self._responseArray: dict = json.loads(responseContent) or {}

    @staticmethod
    def fromPrimitives(responseContent: str) -> "JsonResponseRepositoryMock":
        return JsonResponseRepositoryMock(responseContent)

    @staticmethod
    def fromHttpResponse(httpResponse: Any) -> "JsonResponseRepositoryMock":
        return JsonResponseRepositoryMock(httpResponse.getBody())

    def getCode(self) -> int:
        return int(self._responseArray.get("code") or 0)

    def getStatus(self) -> bool:
        return bool(self._responseArray.get("status") or False)

    def getErrors(self) -> list:
        return self._responseArray.get("errors") or []

    def getData(self) -> dict:
        return self._responseArray.get("data") or {}

    def getDebug(self, useCase: str = "") -> None:
        debug = f"useCase: {useCase}, " if useCase else ""
        debug += f"code: {self.getCode()}, status: {str(self.getStatus()).lower()}, errors: {' | '.join(self.getErrors())}"
        sys.stderr.write(f"{debug}\n")

    def resetAll(self) -> None:
        self._responseArray = {}
