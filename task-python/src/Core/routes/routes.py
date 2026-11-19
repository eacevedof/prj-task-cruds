from Core.Boot.ApiRouter import Route

from Modules.Open.Documentation.Infrastructure.Controllers.ChangelogController import ChangelogController
from Modules.Open.Documentation.Infrastructure.Controllers.DocumentationController import DocumentationController
from Modules.Shared.Infrastructure.Controllers.HealthController import HealthController
from Modules.Users.Infrastructure.Controllers.LogInUserRestController import LogInUserRestController

from Modules.GraphQl.Infrastructure.Controllers.ExecuteGraphQlQueryController import ExecuteGraphQlQueryController


def getRoutes() -> list[Route]:
    return [
        {"url": "/health", "controller": lambda args: HealthController.getInstance().invoke(), "allowed": ["get"]},

        # @documentation (publica; fuente: los .md de src/Modules/Open/Documentation/Infrastructure/Views)
        {"url": "/api/v1", "controller": lambda args: DocumentationController.getInstance().invoke(), "allowed": ["get"]},
        {"url": "/api/v1/changelog", "controller": lambda args: ChangelogController.getInstance().invoke(), "allowed": ["get"]},

        # @auth
        {"url": "/api/v1/log-in", "controller": lambda args: LogInUserRestController.getInstance().invoke(), "allowed": ["post"]},

        # @uses ExecuteGraphQlQueryController
        # @graphql (cada modulo registra su XxxGraphQlSchemaProvider en GraphQlSchemaProvidersRegistry._getAllModulesProviders())
        {
            "url": "/graphql",
            "controller": lambda args: ExecuteGraphQlQueryController.getInstance().invoke(),
            "allowed": ["post"],
        },
    ]
