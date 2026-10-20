<?php
declare(strict_types=1);

namespace App\Modules\Devops\Application\RunMigrations;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class RunMigrationsDto extends AbstractInputDto
{
    public function __construct(array $primitives)
    {
        parent::__construct($primitives);
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }
}
