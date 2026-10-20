<?php

//run.php 2.0.0
include("ComponentConsole.php");

function run_m($text)
{
    echo $text."\n";
}

run_m("running run.php 2.0.0");
/*
php run.php $argv[1]                $argv[2]                    $argv[3]    $argv[4..n]
php run.php <path_file_to_include>  <class_name_case_sensitive> <method>    <rest-of-arguments>
*/
if (defined("STDIN")) {
    run_m("defined STDIN");
    try {
        $runner = new TheFramework\Components\Console\ComponentConsole($argv);
        $runner->setPathclass($argv[1]);
        $runner->setClassname($argv[2]);
        $runner->setMethod($argv[3]);
        $runner->run();
    } catch (Exception $ex) {
        $message = "run.php Exception:{$ex->getMessage()}";
        run_m($message);
    }
} else {
    run_m("Not console called");
}
