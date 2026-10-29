<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Domain\Enums;

abstract class GraphqlMessageEnum
{
    public const string GRAPHQL_QUERY_IS_REQUIRED = "GraphQL query is required";
    public const string GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X = "graphql: {0}.{1} already has a resolver ({2})";
    public const string GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER = "graphql: {0}.{1} has no resolver";
}
