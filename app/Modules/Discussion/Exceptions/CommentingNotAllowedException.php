<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Exceptions;

use App\Shared\Exceptions\BusinessException;

class CommentingNotAllowedException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Active subscription required to post comments.',
            statusCode: 403,
        );
    }
}
