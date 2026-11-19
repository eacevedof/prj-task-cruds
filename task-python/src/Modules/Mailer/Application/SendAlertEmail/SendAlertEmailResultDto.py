from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class SendAlertEmailResultDto:

    def __init__(self, primitives: Primitives):
        # false sin error: APP_EMAIL_TO vacio = alertas por email desactivadas (p.ej. en los tests)
        isSent = primitives.get("isSent")
        self._isSent = bool(False if isSent is None else isSent)
        self._emailTo = getTrimmedString(primitives.get("emailTo"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "SendAlertEmailResultDto":
        return SendAlertEmailResultDto(primitives)

    def isSent(self) -> bool:
        return self._isSent

    def getEmailTo(self) -> str:
        return self._emailTo
