<?php

declare(strict_types=1);

namespace Tests\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController\Mothers;

use App\Modules\Open\Documentation\Infrastructure\Controllers\ChangelogController;
use App\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController;

final class DocumentationControllerMother
{
    public static function getControllerInstance(): DocumentationController
    {
        return new DocumentationController();
    }

    public static function getChangelogControllerInstance(): ChangelogController
    {
        return new ChangelogController();
    }
}
