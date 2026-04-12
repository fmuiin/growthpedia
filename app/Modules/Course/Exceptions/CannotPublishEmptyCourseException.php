<?php

declare(strict_types=1);

namespace App\Modules\Course\Exceptions;

use App\Shared\Exceptions\BusinessException;

class CannotPublishEmptyCourseException extends BusinessException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Course must have at least one lesson to publish', 422, $previous);
    }
}
