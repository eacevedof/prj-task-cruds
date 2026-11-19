import platform

from Modules.Shared.Domain.Enums.HealthKeyEnum import HealthKeyEnum
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Controllers.AbstractRestController import AbstractRestController


class HealthController(AbstractRestController):
    """
    GET /health
    """

    @staticmethod
    def getInstance() -> "HealthController":
        return HealthController()

    def invoke(self) -> HttpResponder:
        return self.respondOk({
            HealthKeyEnum.APP: HealthKeyEnum.APP_NAME,
            HealthKeyEnum.PYTHON: f"v{platform.python_version()}",
            HealthKeyEnum.SQL_DRIVERS: ["sqlite"],
        })
