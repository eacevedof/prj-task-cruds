import type { InterfaceGraphqlSchemaProviderClass } from "#app/Modules/Graphql/Infrastructure/Schema/InterfaceGraphqlSchemaProvider.ts";

import { UsersGraphqlSchemaProvider } from "#app/Modules/Users/Infrastructure/Providers/UsersGraphqlSchemaProvider.ts";
import { TasksGraphqlSchemaProvider } from "#app/Modules/Tasks/Infrastructure/Providers/TasksGraphqlSchemaProvider.ts";

// Registro de los modulos en /graphql (lo lee GraphqlSchemaRegistry solo cuando llega una peticion a /graphql).
// Cada modulo nuevo anade aqui su XxxGraphqlSchemaProvider, despues de los modulos cuyos tipos extiende.
export const graphqlSchemaProviders: readonly InterfaceGraphqlSchemaProviderClass[] = [
    UsersGraphqlSchemaProvider,
    TasksGraphqlSchemaProvider,
];
