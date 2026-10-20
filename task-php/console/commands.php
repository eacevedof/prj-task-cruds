<?php
/*
php console.php <command-alias> --clave=valor .. --clave=valor
Ejemplo:
    php console.php help

mapping:
    <command-alias> => [
        "service" => <namespace-class>,
        "info"    => que hace (una linea),
        "example" => los argumentos de un ejemplo; el help lo imprime como "php console.php <alias> <example>"
    ]
*/
return [
    "help"                   => [
        "service"   =>  "App\\Console\\Application\\HelpCommand",
        "info"      =>  "lista los comandos con un ejemplo de cada uno",
        "example"   =>  "",
    ],

    "run-migrations"         => [
        "service"   =>  "App\\Modules\\Devops\\Infrastructure\\Commands\\RunMigrationsCommand",
        "info"      =>  "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
        "example"   =>  "",
    ],

    // cuenta (users): mismos casos de uso que la API; quien tiene la consola actua como el dueño de la cuenta
    "user-create"            => [
        "service"   =>  "App\\Modules\\Users\\Infrastructure\\Commands\\CreateUserCommand",
        "info"      =>  "alta de cuenta; imprime su api_token (solo esta vez)",
        "example"   =>  "--user_name=ana.lopez --user_email=ana@example.com --password=<password>",
    ],
    "user-get"               => [
        "service"   =>  "App\\Modules\\Users\\Infrastructure\\Commands\\GetUserCommand",
        "info"      =>  "detalle de una cuenta",
        "example"   =>  "--user_uuid=<uuid>",
    ],
    "user-update"            => [
        "service"   =>  "App\\Modules\\Users\\Infrastructure\\Commands\\UpdateUserCommand",
        "info"      =>  "reemplazo completo de nombre y email (los dos obligatorios)",
        "example"   =>  "--user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com",
    ],
    "user-delete"            => [
        "service"   =>  "App\\Modules\\Users\\Infrastructure\\Commands\\DeleteUserCommand",
        "info"      =>  "borrado fisico de la cuenta y sus tareas, sin confirmacion",
        "example"   =>  "--user_uuid=<uuid>",
    ],
    "user-regenerate-api-token" => [
        "service"   =>  "App\\Modules\\Users\\Infrastructure\\Commands\\RegenerateUserApiTokenCommand",
        "info"      =>  "nuevo api_token; el anterior deja de valer",
        "example"   =>  "--user_uuid=<uuid>",
    ],
];
