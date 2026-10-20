<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Traits;

trait ConsoleTrait
{
    protected mixed $input;

    private function pr(mixed $var, string $title = ""): void
    {
        $now = date("Y-m-d H:i:s");
        $message = "\n$now\n";
        if ($title) {
            $message .= $title ."\n";
        }
        $message .= is_string($var) ? $var : var_export($var, true);
        $message .= "\n";
        echo $message;
    }
}//ConsoleTrait
