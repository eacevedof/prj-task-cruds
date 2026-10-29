import type { Route } from "#app/Core/Boot/ApiRouter.ts";

import { ChangelogController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/ChangelogController.ts";
import { DocumentationController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/DocumentationController.ts";
import { HealthController } from "#app/Modules/Shared/Infrastructure/Controllers/HealthController.ts";
import { LogInUserRestController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserRestController.ts";

import { GraphqlController } from "#app/Modules/Graphql/Infrastructure/Controllers/GraphqlController.ts";

/**
 * Rutas de la API (ApiRouter). "url" con tags ":nombre" -> argumento con ese nombre para el controller.
 * "allowed": metodos HTTP en minusculas (HEAD va implicito en GET).
 * Solo login (REST) y GraphQL: el resto de operaciones van por /graphql.
 * Auth: /graphql exige "Authorization: Bearer <api_token>" salvo createUser y logIn. No lo decide el router: cada
 * resolver autentica con getAuthUserOrFail() (AbstractGraphqlController).
 */
export function getRoutes(): Route[] {
    return [
        { url: "/health", controller: () => HealthController.getInstance().index(), allowed: ["get"] },

        //@documentation (publica; fuente: los .md de src/Modules/Open/Documentation/Infrastructure/Views)
        { url: "/api/v1", controller: () => DocumentationController.getInstance().show(), allowed: ["get"] },
        { url: "/api/v1/changelog", controller: () => ChangelogController.getInstance().show(), allowed: ["get"] },

        //@auth
        { url: "/api/v1/log-in", controller: () => LogInUserRestController.getInstance().logIn(), allowed: ["post"] },

        //@graphql (cada modulo se registra en src/Core/config/graphqlSchema.ts con su XxxGraphqlSchemaProvider)
        { url: "/graphql", controller: () => GraphqlController.getInstance().execute(), allowed: ["post"] },
    ];
}
