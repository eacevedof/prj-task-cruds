package core.routes;

import java.util.List;

import core.boot.ApiRouter.Route;

import modules.open.documentation.infrastructure.controllers.ChangelogController;
import modules.open.documentation.infrastructure.controllers.DocumentationController;
import modules.shared.infrastructure.controllers.HealthController;
import modules.users.infrastructure.controllers.LogInUserRestController;

import modules.graphql.infrastructure.controllers.ExecuteGraphQlQueryController;

/**
 * Rutas de la API (ApiRouter). "url" con tags ":nombre" -> argumento con ese nombre para el controller.
 * "allowed": metodos HTTP en minusculas (HEAD va implicito en GET).
 * Solo login (REST) y GraphQL: el resto de operaciones van por /graphql.
 * Auth: /graphql exige "Authorization: Bearer <api_token>" salvo createUser y logIn. No lo decide el router: cada
 * resolver autentica con getAuthUserOrFail() (AbstractHttpController).
 */
public final class Routes {

    private Routes() {
    }

    public static List<Route> getRoutes() {
        return List.of(
            new Route("/health", args -> HealthController.getInstance().invoke(), List.of("get")),

            //@documentation (publica; fuente: los .md de src/modules/open/documentation/infrastructure/views)
            new Route("/api/v1", args -> DocumentationController.getInstance().invoke(), List.of("get")),
            new Route("/api/v1/changelog", args -> ChangelogController.getInstance().invoke(), List.of("get")),

            //@auth
            new Route("/api/v1/log-in", args -> LogInUserRestController.getInstance().invoke(), List.of("post")),

            //@graphql (cada modulo registra su XxxGraphQlSchemaProvider en GraphQlSchemaProvidersRegistry.getAllModulesProviders())
            new Route("/graphql", args -> ExecuteGraphQlQueryController.getInstance().invoke(), List.of("post"))
        );
    }
}
