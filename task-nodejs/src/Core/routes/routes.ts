import type { Route } from "#app/Core/Boot/ApiRouter.ts";
import { GraphqlController } from "#app/Core/Graphql/GraphqlController.ts";

import { ChangelogController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/ChangelogController.ts";
import { DocumentationController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/DocumentationController.ts";
import { HealthController } from "#app/Modules/Shared/Infrastructure/Controllers/HealthController.ts";
import { LogInUserController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserController.ts";

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
        { url: "/api/v1/log-in", controller: () => LogInUserController.getInstance().logIn(), allowed: ["post"] },

        //@graphql (schema en src/Core/Graphql/schema.graphql)
        { url: "/graphql", controller: () => GraphqlController.getInstance().execute(), allowed: ["post"] },
    ];
}
