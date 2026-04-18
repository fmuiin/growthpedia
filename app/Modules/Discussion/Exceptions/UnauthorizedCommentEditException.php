<?php

declare(strict_types=1);

namespace App\Modules\Discussion\Exceptions;

use App\Shared\Exceptions\BusinessException;

class UnauthorizedCommentEditException extends BusinessException
{
    public function __construct()
    {
        parent::__construct(
            message: 'You can only edit your own comments.',
            statusCode: 403,
        );
    }
}
