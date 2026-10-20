<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Mailer;

interface InterfaceMailer
{
    public function send(): void;
}
