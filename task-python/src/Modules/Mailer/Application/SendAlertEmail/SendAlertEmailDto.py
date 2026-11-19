from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class SendAlertEmailDto:

    def __init__(self, primitives: Primitives):
        """
        primitives: alertType (AlertEmailTypeEnum), subject, details (texto plano: el servicio lo escapa)
        """
        self._alertType = getTrimmedString(primitives.get("alertType"))
        self._subject = getTrimmedString(primitives.get("subject"))
        self._details = getTrimmedString(primitives.get("details"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "SendAlertEmailDto":
        return SendAlertEmailDto(primitives)

    def getAlertType(self) -> str:
        return self._alertType

    def getSubject(self) -> str:
        return self._subject

    def getDetails(self) -> str:
        return self._details
