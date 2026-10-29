package core.routes;

import java.util.List;

import core.boot.ApiRouter.Route;

import modules.open.documentation.infrastructure.controllers.ChangelogController;
import modules.open.documentation.infrastructure.controllers.DocumentationController;
import modules.shared.infrastructure.controllers.HealthController;
import modules.users.infrastructure.controllers.LogInUserRestController;

import modules.graphql.infrastructure.controllers.GraphqlController;

/**
 * Rutas de la API (ApiRouter). "url" con tags ":nombre" -> argumento con ese nombre para el controller.
 * "allowed": metodos HTTP en minusculas (HEAD va implicito en GET).
 * Solo login (REST) y GraphQL: el resto de operaciones van por /graphql.
 * Auth: /graphql exige "Authorization: Bearer <api_token>" salvo createUser y logIn. No lo decide el router: cada
 * resolver autentica con getAuthUserOrFail() (AbstractGraphqlController).
 */
public final class Routes {

    private Routes() {
    }

    public static List<Route> getRoutes() {
        return List.of(
            new Route("/health", args -> HealthController.getInstance().index(), List.of("get")),

            //@documentation (publica; fuente: los .md de src/modules/open/documentation/infrastructure/views)
            new Route("/api/v1", args -> DocumentationController.getInstance().show(), List.of("get")),
            new Route("/api/v1/changelog", args -> ChangelogController.getInstance().show(), List.of("get")),

            //@auth
            new Route("/api/v1/log-in", args -> LogInUserRestController.getInstance().logIn(), List.of("post")),

            //@graphql (cada modulo se registra en src/core/config/GraphqlSchema.java con su XxxGraphqlSchemaProvider)
            new Route("/graphql", args -> GraphqlController.getInstance().execute(), List.of("post"))
        );
    }
}
