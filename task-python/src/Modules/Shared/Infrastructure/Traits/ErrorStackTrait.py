class ErrorStackTrait:
    """
    Un servicio no guarda errores: si falla, escala con su excepcion y ahi acaba. Este trait solo da formato a
    una pila de errores (p.ej. la de un validador) para meterla en el mensaje de esa excepcion:
    FieldsException.badRequestCustom(self._errorStackTrait.getErrorStackMessage(__(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors))
    """

    @staticmethod
    def getInstance() -> "ErrorStackTrait":
        return ErrorStackTrait()

    def getErrorStackMessage(self, title: str, errorStack: list[str]) -> str:
        """
        @returns "Fields validation errors: user_name: field is required; user_email: invalid email"
        """
        return f"{title}: {'; '.join(errorStack)}"
