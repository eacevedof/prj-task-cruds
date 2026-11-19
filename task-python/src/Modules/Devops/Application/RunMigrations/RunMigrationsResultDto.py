from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class RunMigrationsResultDto:

    def __init__(self, primitives: Primitives):
        # traza de lo ocurrido, en orden: la imprime el command (el servicio no escribe en stdout)
        messages = primitives.get("messages")
        self._messages: list[str] = [
            getTrimmedString(message) for message in (messages if isinstance(messages, list) else [])
        ]

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "RunMigrationsResultDto":
        return RunMigrationsResultDto(primitives)

    def getMessages(self) -> list[str]:
        return self._messages
