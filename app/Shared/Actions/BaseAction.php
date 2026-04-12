<?php

declare(strict_types=1);

namespace App\Shared\Actions;

abstract class BaseAction
{
    abstract public function execute(): mixed;
}
