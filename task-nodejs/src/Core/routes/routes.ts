import type { Route } from "#app/Core/Boot/ApiRouter.ts";

import { ChangelogController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/ChangelogController.ts";
import { DocumentationController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/DocumentationController.ts";
import { HealthController } from "#app/Modules/Shared/Infrastructure/Controllers/HealthController.ts";
import { LogInUserRestController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserRestController.ts";

import { ExecuteGraphQlQueryController } from "#app/Modules/GraphQl/Infrastructure/Controllers/ExecuteGraphQlQueryController.ts";

export function getRoutes(): Route[] {
    return [
        { url: "/health", controller: () => HealthController.getInstance().invoke(), allowed: ["get"] },

        //@documentation (publica; fuente: los .md de src/Modules/Open/Documentation/Infrastructure/Views)
        { url: "/api/v1", controller: () => DocumentationController.getInstance().invoke(), allowed: ["get"] },
        { url: "/api/v1/changelog", controller: () => ChangelogController.getInstance().invoke(), allowed: ["get"] },

        //@auth
        { url: "/api/v1/log-in", controller: () => LogInUserRestController.getInstance().invoke(), allowed: ["post"] },

        /**
         * @uses ExecuteGraphQlQueryController
         * @graphql (cada modulo registra su XxxGraphQlSchemaProvider en GraphQlSchemaProvidersRegistry.getAllModulesProviders())
         */
        {
            url: "/graphql",
            controller: () => ExecuteGraphQlQueryController.getInstance().invoke(),
            allowed: ["post"],
        },
    ];
}
