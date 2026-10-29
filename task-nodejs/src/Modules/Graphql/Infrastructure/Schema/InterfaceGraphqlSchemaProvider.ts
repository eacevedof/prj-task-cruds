import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

/**
 * fn(args, parent): devuelve el objeto del ResultDto o lanza un GraphQLError.
 */
export type GraphqlFieldResolver = (args: Primitives, parent: Primitives) => unknown;

/**
 * Tipo => campo => resolver.
 */
export type GraphqlResolversByType = Record<string, Record<string, GraphqlFieldResolver>>;

/**
 * Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
 * Infrastructure/Providers y se registra en src/Core/config/graphqlSchema.ts.
 */
export interface InterfaceGraphqlSchemaProvider {

    /**
     * SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
     */
    getSdl(): string;

    /**
     * Tipo => campo => fn(args, parent): devuelve el objeto del ResultDto o lanza un GraphQLError.
     */
    getResolversByType(): GraphqlResolversByType;
}

/**
 * La clase del proveedor, que es lo que se registra (el getInstance() estatico de PHP: un interface de TS no lo admite).
 */
export interface InterfaceGraphqlSchemaProviderClass {
    readonly name: string;
    getInstance(): InterfaceGraphqlSchemaProvider;
}
