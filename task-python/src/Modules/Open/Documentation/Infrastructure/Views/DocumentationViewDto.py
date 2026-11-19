from Modules.Shared.Application.Primitives import getTrimmedString


class DocumentationViewDto:

    def __init__(self, appVersion: str, appVersionUpdate: str, appBaseUrl: str, errors: list[str]):
        self._appVersion = appVersion
        self._appVersionUpdate = appVersionUpdate
        self._appBaseUrl = appBaseUrl
        self._errors = errors

    @staticmethod
    def fromPrimitives(primitives: dict) -> "DocumentationViewDto":
        errors = primitives.get("errors")
        return DocumentationViewDto(
            getTrimmedString(primitives.get("appVersion")),
            getTrimmedString(primitives.get("appVersionUpdate")),
            getTrimmedString(primitives.get("appBaseUrl")),
            errors if isinstance(errors, list) else [],
        )

    def getAppVersion(self) -> str:
        return self._appVersion

    def getAppVersionUpdate(self) -> str:
        return self._appVersionUpdate

    def getAppBaseUrl(self) -> str:
        return self._appBaseUrl

    def getErrors(self) -> list[str]:
        return self._errors
