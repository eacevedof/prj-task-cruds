<?php
/*
php	backend/console/console.php <command-alias> param1 param2 .. paramN
Ejemplo:
    php console.php help

./cmd <command-alias> <parameters>
mapping:
    <command-alias> => <namespace-class>
*/
return [
    "help"                   => [
        "service"   =>  "App\\Console\\Application\\HelpService",
        "info"      =>  "",
    ],

    "run-migrations"         => [
        "service"   =>  "App\\Console\\Application\\Devops\\RunMigrationsCommand",
        "info"      =>  "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
    ],
];
