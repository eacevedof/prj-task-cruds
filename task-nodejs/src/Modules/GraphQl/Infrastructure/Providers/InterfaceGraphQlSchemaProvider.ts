import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

/**
 * fn(gqlPayload, parent): devuelve el objeto del ResultDto o lanza una GraphQlException.
 */
export type GraphQlFieldResolver = (gqlPayload: Primitives, parent: Primitives) => unknown;

/**
 * Tipo => campo => resolver.
 */
export type GraphQlResolversByType = Record<string, Record<string, GraphQlFieldResolver>>;

/**
 * Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
 * Infrastructure/Providers y se registra en GraphQlSchemaProvidersRegistry.getAllModulesProviders().
 */
export interface InterfaceGraphQlSchemaProvider {

    /**
     * SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
     */
    getGraphQlFileContent(): string;

    /**
     * Tipo => campo => fn(gqlPayload, parent): devuelve el objeto del ResultDto o lanza una GraphQlException.
     */
    getControllerResolvers(): GraphQlResolversByType;
}

/**
 * La clase del proveedor, que es lo que se registra (el getInstance() estatico de PHP: un interface de TS no lo admite).
 */
export interface InterfaceGraphQlSchemaProviderClass {
    readonly name: string;
    getInstance(): InterfaceGraphQlSchemaProvider;
}
