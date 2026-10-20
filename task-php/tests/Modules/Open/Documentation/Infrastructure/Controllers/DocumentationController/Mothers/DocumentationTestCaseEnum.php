<?php

declare(strict_types=1);

namespace Tests\Modules\Open\Documentation\Infrastructure\Controllers\DocumentationController\Mothers;

enum DocumentationTestCaseEnum
{
    case OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN;
    case OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN;
}
