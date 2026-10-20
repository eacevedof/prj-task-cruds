<?php
declare(strict_types=1);

namespace App\Modules\Devops\Application\RunMigrations;

final class RunMigrationsDto
{
    public static function fromPrimitives(): self
    {
        return new self();
    }
}
